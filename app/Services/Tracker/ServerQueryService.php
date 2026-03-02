<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\Log;

class ServerQueryService
{
    private int $timeout;
    private int $retries;

    public function __construct(int $timeout = 3, int $retries = 2)
    {
        $this->timeout = $timeout;
        $this->retries = $retries;
    }

    /**
     * Query a single game server for its status.
     * Returns parsed server info + player list, or null on failure.
     */
    public function queryServer(string $ip, int $port): ?array
    {
        $packet = "\xFF\xFF\xFF\xFFgetstatus\n";

        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            $response = $this->sendUdp($ip, $port, $packet);
            if ($response !== null) {
                return $this->parseStatusResponse($response);
            }
        }

        return null;
    }

    /**
     * Query a single server for basic info (lighter than getstatus).
     */
    public function queryServerInfo(string $ip, int $port): ?array
    {
        $packet = "\xFF\xFF\xFF\xFFgetinfo xxx\n";

        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            $response = $this->sendUdp($ip, $port, $packet);
            if ($response !== null) {
                return $this->parseInfoResponse($response);
            }
        }

        return null;
    }

    /**
     * Query a master server for a list of game servers.
     * Returns array of ['ip' => '...', 'port' => ...] entries.
     */
    public function queryMasterServer(string $address, int $port, int $protocol): array
    {
        $packet = "\xFF\xFF\xFF\xFFgetservers {$protocol} empty full\n";
        $response = $this->sendUdp($address, $port, $packet, 5);

        if ($response === null) {
            return [];
        }

        return $this->parseMasterResponse($response);
    }

    /**
     * Parse a statusResponse packet.
     * Format: \xff\xff\xff\xffstatusResponse\n\\key\\value\\key\\value...\n
     *         score ping "name"\n
     */
    public function parseStatusResponse(string $raw): array
    {
        $lines = explode("\n", $raw);
        $settings = [];
        $players = [];

        // First line is header (statusResponse)
        // Second line is settings
        if (count($lines) >= 2) {
            $settingsLine = $lines[1] ?? '';
            $settings = $this->parseSettings($settingsLine);
        }

        // Remaining lines are players
        for ($i = 2; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $player = $this->parsePlayerLine($line);
            if ($player !== null) {
                $players[] = $player;
            }
        }

        return [
            'settings' => $settings,
            'players' => $players,
        ];
    }

    /**
     * Parse an infoResponse packet.
     */
    public function parseInfoResponse(string $raw): array
    {
        $lines = explode("\n", $raw);
        $settings = [];

        if (count($lines) >= 2) {
            $settings = $this->parseSettings($lines[1] ?? '');
        }

        return ['settings' => $settings];
    }

    /**
     * Parse key-value settings string: \\key\\value\\key\\value
     */
    private function parseSettings(string $line): array
    {
        $settings = [];
        $parts = explode('\\', ltrim($line, '\\'));

        for ($i = 0; $i < count($parts) - 1; $i += 2) {
            $key = $parts[$i];
            $value = $parts[$i + 1] ?? '';
            if (!empty($key)) {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    /**
     * Parse a player line: score ping "name"
     */
    private function parsePlayerLine(string $line): ?array
    {
        // Format: score ping "name"
        if (preg_match('/^(-?\d+)\s+(\d+)\s+"(.*)"$/', $line, $matches)) {
            return [
                'score' => (int)$matches[1],
                'ping' => (int)$matches[2],
                'name' => $matches[3],
            ];
        }

        // Alternative format without quotes: score ping name
        if (preg_match('/^(-?\d+)\s+(\d+)\s+(.+)$/', $line, $matches)) {
            return [
                'score' => (int)$matches[1],
                'ping' => (int)$matches[2],
                'name' => trim($matches[3], '"'),
            ];
        }

        return null;
    }

    /**
     * Parse master server response.
     * Servers are encoded as 6-byte blocks: 4 bytes IP + 2 bytes port
     * Prefixed with \xff\xff\xff\xffgetserversResponse and separated by \
     */
    private function parseMasterResponse(string $raw): array
    {
        $servers = [];

        // Remove header(s) - may contain multiple getserversResponse headers
        $data = $raw;
        while (($pos = strpos($data, "\\")) !== false) {
            $data = substr($data, $pos + 1);
            break;
        }

        // Each server entry is 6 bytes after a backslash
        $parts = explode("\\", $data);

        foreach ($parts as $part) {
            if (strlen($part) === 6) {
                $ip = ord($part[0]) . '.' . ord($part[1]) . '.' . ord($part[2]) . '.' . ord($part[3]);
                $port = (ord($part[4]) << 8) | ord($part[5]);

                // Validate
                if ($port > 0 && $port < 65536 && $ip !== '0.0.0.0') {
                    $servers[] = ['ip' => $ip, 'port' => $port];
                }
            }
        }

        // Deduplicate
        $unique = [];
        foreach ($servers as $s) {
            $key = $s['ip'] . ':' . $s['port'];
            $unique[$key] = $s;
        }

        return array_values($unique);
    }

    /**
     * Send a UDP packet and receive the response.
     */
    private function sendUdp(string $ip, int $port, string $packet, ?int $timeout = null): ?string
    {
        $timeout = $timeout ?? $this->timeout;

        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            Log::warning("Tracker: Failed to create UDP socket");
            return null;
        }

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $timeout,
            'usec' => 0,
        ]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, [
            'sec' => $timeout,
            'usec' => 0,
        ]);

        $sent = @socket_sendto($socket, $packet, strlen($packet), 0, $ip, $port);
        if ($sent === false) {
            socket_close($socket);
            return null;
        }

        $response = '';
        $buf = '';

        // Read potentially multiple packets (master server sends multiple)
        while (true) {
            $bytes = @socket_recvfrom($socket, $buf, 65535, 0, $fromIp, $fromPort);
            if ($bytes === false || $bytes === 0) {
                break;
            }
            $response .= $buf;

            // For game servers, one packet is enough
            if (strpos($response, 'statusResponse') !== false || strpos($response, 'infoResponse') !== false) {
                break;
            }

            // For master servers, check for EOT marker
            if (strpos($buf, 'EOT') !== false) {
                break;
            }
        }

        socket_close($socket);

        return !empty($response) ? $response : null;
    }
}
