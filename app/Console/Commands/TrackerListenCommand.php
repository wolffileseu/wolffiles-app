<?php

namespace App\Console\Commands;

use Throwable;
use App\Jobs\ProcessTrackerEventJob;
use App\Models\Tracker\TrackerRawEvent;
use App\Services\Tracker\EnhancedTrackerPacketParser;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * UDP listener daemon for the Enhanced Tracker.
 *
 * Listens on the configured UDP port, accepts sv_tracker2 packets from ET
 * servers, persists each packet into tracker_raw_events, and dispatches
 * ProcessTrackerEventJob for async deep parsing.
 *
 * Designed to run forever under supervisord. Exits cleanly on SIGTERM or
 * after `soft_restart_after_seconds` so supervisord can give us a fresh
 * PHP process (avoids long-run memory creep).
 */
class TrackerListenCommand extends Command
{
    protected $signature = 'tracker:listen
        {--dry-run : Print packets but don\'t write to DB or dispatch jobs}
        {--max-packets= : Stop after this many packets (for testing)}
        {--duration= : Stop after this many seconds (for testing)}';

    protected $description = 'Run the Enhanced Tracker UDP listener daemon';

    private EnhancedTrackerPacketParser $parser;

    /** @var resource|null */
    private $socket = null;

    private bool $shouldStop = false;
    private int $packetsReceived = 0;
    private int $packetsAccepted = 0;
    private int $packetsRejected = 0;
    private int $startTime = 0;

    /** Rate limit buckets: ['ip' => [timestamp => count, ...]] */
    private array $rateBuckets = [];

    public function __construct()
    {
        parent::__construct();
        $this->parser = new EnhancedTrackerPacketParser();
    }

    public function handle(): int
    {
        $this->startTime = time();

        $host = (string) config('tracker.listen.host', '0.0.0.0');
        $port = (int) config('tracker.listen.port', 4444);
        $bufferSize = (int) config('tracker.listen.buffer_size', 65535);
        $timeoutMs = (int) config('tracker.listen.socket_timeout_ms', 500);
        $softRestart = (int) config('tracker.listen.soft_restart_after_seconds', 3600);
        $dryRun = (bool) $this->option('dry-run');
        $maxPackets = $this->option('max-packets') ? (int) $this->option('max-packets') : null;
        $duration = $this->option('duration') ? (int) $this->option('duration') : null;

        $this->registerSignalHandlers();
        if (!$this->openSocket($host, $port, $timeoutMs)) {
            return Command::FAILURE;
        }

        $this->info(sprintf(
            'Enhanced Tracker listening on udp://%s:%d%s',
            $host,
            $port,
            $dryRun ? ' (DRY-RUN)' : ''
        ));

        try {
            while (!$this->shouldStop) {
                // Soft-restart check
                if ($softRestart > 0 && (time() - $this->startTime) >= $softRestart) {
                    $this->info('Soft-restart threshold reached, exiting cleanly for supervisord to restart us');
                    break;
                }

                // Test-mode limits
                if ($maxPackets !== null && $this->packetsReceived >= $maxPackets) {
                    $this->info("max-packets={$maxPackets} reached, exiting");
                    break;
                }
                if ($duration !== null && (time() - $this->startTime) >= $duration) {
                    $this->info("duration={$duration}s reached, exiting");
                    break;
                }

                // Allow pending signals to fire
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }

                $this->receiveOnce($bufferSize, $dryRun);
            }
        } finally {
            $this->closeSocket();
            $this->printStats();
        }

        return Command::SUCCESS;
    }

    /**
     * Receive one UDP datagram (blocking up to socket_timeout_ms), then return.
     * Returning to the main loop lets us check for SIGTERM and soft-restart.
     */
    private function receiveOnce(int $bufferSize, bool $dryRun): void
    {
        $from = '';
        $port = 0;

        // @-suppress: on timeout socket_recvfrom emits a warning. We handle it by
        // checking the return value, so the warning is noise.
        $bytes = @socket_recvfrom($this->socket, $data, $bufferSize, 0, $from, $port);

        if ($bytes === false || $bytes <= 0) {
            // Timeout. Just return to main loop.
            return;
        }

        $this->packetsReceived++;
        $receivedAt = Carbon::now();

        if (!$this->shouldAcceptFromIp($from)) {
            $this->packetsRejected++;
            return;
        }

        $envelope = $this->parser->parseEnvelope($data);

        if (config('tracker.listen.verbose_logging')) {
            $this->line(sprintf(
                '[%s] %s:%d %s %s (%d bytes)%s',
                $receivedAt->format('H:i:s.v'),
                $from,
                $port,
                $envelope['valid'] ? '✓' : '✗',
                $envelope['cmd'] ?? '?',
                $bytes,
                $dryRun ? ' [DRY]' : '',
            ));
        }

        if (!$envelope['valid']) {
            $this->packetsRejected++;
            return;
        }

        $this->packetsAccepted++;

        if ($dryRun) {
            return;
        }

        $this->persist($envelope, $from, $port, $receivedAt);
    }

    /**
     * Persist the event and dispatch the async processing job.
     * All writes wrapped in a try/catch — if the DB is briefly unavailable we
     * log and continue rather than killing the listener.
     */
    private function persist(array $envelope, string $sourceIp, int $sourcePort, Carbon $receivedAt): void
    {
        try {
            $event = TrackerRawEvent::create([
                'received_at' => $receivedAt,
                'source_ip' => $sourceIp,
                'source_port' => $sourcePort,
                'cmd' => $envelope['cmd'],
                'size_bytes' => $envelope['size'],
                'payload' => $envelope['payload'],
                'processed' => false,
            ]);

            ProcessTrackerEventJob::dispatch($event->id);
        } catch (Throwable $e) {
            Log::error('Tracker listener: failed to persist packet', [
                'src' => "{$sourceIp}:{$sourcePort}",
                'cmd' => $envelope['cmd'] ?? '?',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check IP allow/block lists and per-IP rate limit.
     * Returns false to silently drop the packet.
     */
    private function shouldAcceptFromIp(string $ip): bool
    {
        $blocked = (array) config('tracker.auth.blocked_ips', []);
        if (in_array($ip, $blocked, true)) {
            return false;
        }

        $allowed = (array) config('tracker.auth.allowed_ips', []);
        if (!empty($allowed) && !in_array($ip, $allowed, true)) {
            return false;
        }

        $maxPerSecond = (int) config('tracker.auth.rate_limit_per_ip_per_second', 0);
        if ($maxPerSecond > 0) {
            $now = time();
            // Clean out old buckets
            if (isset($this->rateBuckets[$ip])) {
                foreach ($this->rateBuckets[$ip] as $ts => $_) {
                    if ($ts < $now - 1) {
                        unset($this->rateBuckets[$ip][$ts]);
                    }
                }
            }
            $current = $this->rateBuckets[$ip][$now] ?? 0;
            if ($current >= $maxPerSecond) {
                return false;
            }
            $this->rateBuckets[$ip][$now] = $current + 1;
        }

        return true;
    }

    private function openSocket(string $host, int $port, int $timeoutMs): bool
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            $this->error('socket_create failed: ' . socket_strerror(socket_last_error()));
            return false;
        }

        // Allow quick restart without "address already in use"
        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->error('socket_set_option SO_REUSEADDR failed');
            return false;
        }

        // Short recv timeout so the main loop can check for signals & soft-restart
        $sec = intdiv($timeoutMs, 1000);
        $usec = ($timeoutMs % 1000) * 1000;
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $sec, 'usec' => $usec]);

        if (!@socket_bind($socket, $host, $port)) {
            $err = socket_strerror(socket_last_error($socket));
            $this->error("Could not bind to {$host}:{$port} — {$err}");
            socket_close($socket);
            return false;
        }

        $this->socket = $socket;
        return true;
    }

    private function closeSocket(): void
    {
        if ($this->socket !== null) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $this->shouldStop = true);
        pcntl_signal(SIGINT, fn () => $this->shouldStop = true);
        pcntl_signal(SIGQUIT, fn () => $this->shouldStop = true);
    }

    private function printStats(): void
    {
        $duration = max(1, time() - $this->startTime);
        $this->newLine();
        $this->line('--- Listener Stats ---');
        $this->line(sprintf('Duration:          %d seconds', $duration));
        $this->line(sprintf('Packets received:  %d', $this->packetsReceived));
        $this->line(sprintf('Packets accepted:  %d', $this->packetsAccepted));
        $this->line(sprintf('Packets rejected:  %d', $this->packetsRejected));
        $this->line(sprintf('Throughput:        %.2f pkt/sec', $this->packetsReceived / $duration));
    }
}
