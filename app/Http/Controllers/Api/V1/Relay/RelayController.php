<?php

namespace App\Http\Controllers\Api\V1\Relay;

use App\Http\Controllers\Controller;
use App\Models\RelayNode;
use App\Models\RelaySession;
use App\Models\Tracker\TrackerGame;
use App\Models\Tracker\TrackerServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RelayController extends Controller
{
    /**
     * How long a freshly issued ticket stays valid before the agent rejects it.
     */
    private const TICKET_TTL_SECONDS = 60;

    /**
     * Issue a signed ticket for a browser client that wants to join a server.
     *
     * The target address is resolved server-side from tracker_servers on
     * purpose: the client never gets to pick an arbitrary UDP destination,
     * which is what would turn the relay into an open proxy.
     */
    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'region'    => ['nullable', 'string', 'max:32'],
        ]);

        $server = TrackerServer::query()
            ->where('id', $validated['server_id'])
            ->where('status', 'active')
            ->first();

        if (! $server) {
            return response()->json([
                'error' => 'server_not_found',
                'message' => 'This server is not available for browser play.',
            ], 404);
        }

        if ($server->needs_password) {
            return response()->json([
                'error' => 'server_password_protected',
                'message' => 'Password protected servers are not supported yet.',
            ], 422);
        }

        $node = RelayNode::query()
            ->available()
            ->inRegion($validated['region'] ?? null)
            ->orderBy('active_sessions')
            ->first();

        // Fall back to any healthy node if the requested region is exhausted
        if (! $node && ! empty($validated['region'])) {
            $node = RelayNode::query()
                ->available()
                ->orderBy('active_sessions')
                ->first();
        }

        if (! $node) {
            return response()->json([
                'error' => 'no_relay_available',
                'message' => 'No relay node has free capacity right now.',
            ], 503);
        }

        $game = $this->resolveGame($server);

        if ($game === null) {
            return response()->json([
                'error' => 'unsupported_game',
                'message' => 'This server does not run a game the browser client supports.',
            ], 422);
        }

        $session = new RelaySession([
            'relay_node_id'     => $node->id,
            'tracker_server_id' => $server->id,
            'user_id'           => $request->user()?->id,
            'game'              => $game,
            'target_ip'         => $server->ip,
            'target_port'       => $server->port,
            'client_ip'         => $request->ip(),
            'ticket_id'         => (string) Str::uuid(),
            'ticket_expires_at' => now()->addSeconds(self::TICKET_TTL_SECONDS),
        ]);
        $session->save();

        $token = $this->signTicket($node, [
            'tid'  => $session->ticket_id,
            'ip'   => $server->ip,
            'port' => (int) $server->port,
            'game' => $game,
            'exp'  => $session->ticket_expires_at->timestamp,
        ]);

        return response()->json([
            'ws_url'     => rtrim($node->ws_url, '/') . '/?t=' . $token,
            'ticket_id'  => $session->ticket_id,
            'expires_at' => $session->ticket_expires_at->toIso8601String(),
            'node'       => [
                'name'   => $node->name,
                'region' => $node->region,
            ],
            'server'     => [
                'id'       => $server->id,
                'hostname' => $server->hostname_clean,
                'map'      => $server->current_map,
                'mod'      => $server->mod_name,
                'game'     => $game,
            ],
        ]);
    }

    /**
     * Agent heartbeat. Authenticated by HMAC over the raw request body.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $nodeId = (int) $request->input('node_id');

        $node = RelayNode::find($nodeId);

        if (! $node) {
            return response()->json(['error' => 'unknown_node'], 404);
        }

        if (! $this->verifyAgentSignature($request, $node)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $data = $request->validate([
            'node_id'         => ['required', 'integer'],
            'active_sessions' => ['required', 'integer', 'min:0'],
            'load_avg'        => ['nullable', 'numeric'],
            'agent_version'   => ['nullable', 'string', 'max:32'],
            'status'          => ['nullable', 'in:online,degraded'],
        ]);

        $node->fill([
            'active_sessions'   => $data['active_sessions'],
            'load_avg'          => $data['load_avg'] ?? null,
            'agent_version'     => $data['agent_version'] ?? $node->agent_version,
            'status'            => $node->enabled ? ($data['status'] ?? 'online') : 'disabled',
            'last_heartbeat_at' => now(),
        ])->save();

        return response()->json([
            'ok'           => true,
            'enabled'      => $node->enabled,
            'max_sessions' => $node->max_sessions,
        ]);
    }

    /**
     * Agent reports a session start/stop with traffic counters.
     */
    public function session(Request $request): JsonResponse
    {
        $nodeId = (int) $request->input('node_id');

        $node = RelayNode::find($nodeId);

        if (! $node) {
            return response()->json(['error' => 'unknown_node'], 404);
        }

        if (! $this->verifyAgentSignature($request, $node)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $data = $request->validate([
            'node_id'      => ['required', 'integer'],
            'ticket_id'    => ['required', 'string', 'size:36'],
            'event'        => ['required', 'in:start,end'],
            'source_addr'  => ['nullable', 'string', 'max:45'],
            'bytes_in'     => ['nullable', 'integer', 'min:0'],
            'bytes_out'    => ['nullable', 'integer', 'min:0'],
            'reason'       => ['nullable', 'string', 'max:32'],
        ]);

        $session = RelaySession::query()
            ->where('ticket_id', $data['ticket_id'])
            ->where('relay_node_id', $node->id)
            ->first();

        if (! $session) {
            return response()->json(['error' => 'unknown_session'], 404);
        }

        if ($data['event'] === 'start') {
            $session->fill([
                'started_at'  => now(),
                'source_addr' => $data['source_addr'] ?? null,
            ])->save();
        } else {
            $session->fill([
                'ended_at'     => now(),
                'ended_reason' => $data['reason'] ?? 'closed',
                'bytes_in'     => $data['bytes_in'] ?? $session->bytes_in,
                'bytes_out'    => $data['bytes_out'] ?? $session->bytes_out,
            ])->save();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Map a tracked server onto the client build that can play it.
     *
     * Returns null when the server runs something we have no WASM client
     * for (CoD, Quake 3) or is not a playable endpoint at all (ETTV).
     */
    private function resolveGame(TrackerServer $server): ?string
    {
        $family = strtolower((string) $server->engine_family);

        if ($family !== '') {
            foreach (['cod', 'quake3', 'ettv'] as $foreign) {
                if (str_starts_with($family, $foreign)) {
                    return null;
                }
            }

            if (str_starts_with($family, 'rtcw')) {
                return 'rtcw';
            }

            if (str_starts_with($family, 'et_')) {
                return 'et';
            }
        }

        // engine_family is only populated while a server is polled online,
        // so fall back to the game it is registered under.
        $slug = strtolower((string) TrackerGame::query()
            ->where('id', $server->game_id)
            ->value('slug'));

        if ($slug === '') {
            return null;
        }

        if (str_starts_with($slug, 'rtcw')) {
            return 'rtcw';
        }

        if (str_starts_with($slug, 'et')) {
            return 'et';
        }

        return null;
    }

    /**
     * base64url(payload) . "." . base64url(hmac_sha256(payload, secret))
     *
     * The agent validates this without touching the database.
     */
    private function signTicket(RelayNode $node, array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $body = $this->b64url($json);
        $sig  = $this->b64url(hash_hmac('sha256', $body, $node->agent_secret, true));

        return $body . '.' . $sig;
    }

    private function verifyAgentSignature(Request $request, RelayNode $node): bool
    {
        $provided = (string) $request->header('X-Relay-Signature');

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $node->agent_secret);

        return hash_equals($expected, $provided);
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
