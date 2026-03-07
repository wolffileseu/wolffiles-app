<?php

namespace App\Services;

use App\Models\EttvSlot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PterodactylService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.pterodactyl.url', 'https://panel.wolffiles.eu'), '/');
        $this->apiKey = config('services.pterodactyl.api_key');
    }

    public function startServer(EttvSlot $slot): bool
    {
        return $this->sendPowerSignal($slot->pterodactyl_uuid, 'start');
    }

    public function stopServer(EttvSlot $slot): bool
    {
        return $this->sendPowerSignal($slot->pterodactyl_uuid, 'stop');
    }

    public function killServer(EttvSlot $slot): bool
    {
        return $this->sendPowerSignal($slot->pterodactyl_uuid, 'kill');
    }

    protected function sendPowerSignal(string $uuid, string $signal): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/api/client/servers/{$uuid}/power", [
                    'signal' => $signal,
                ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Pterodactyl power: {$e->getMessage()}", compact('uuid', 'signal'));
            return false;
        }
    }

    public function setVariables(EttvSlot $slot, array $variables): bool
    {
        $success = true;
        foreach ($variables as $key => $value) {
            if (!$this->updateVariable($slot, $key, (string) $value)) {
                $success = false;
                Log::error("Failed to set {$key}={$value} on slot {$slot->slot_number}");
            }
        }
        return $success;
    }

    public function updateVariable(EttvSlot $slot, string $key, string $value): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->put("{$this->baseUrl}/api/client/servers/{$slot->pterodactyl_uuid}/startup/variable", [
                    'key' => $key,
                    'value' => $value,
                ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Variable update failed: {$e->getMessage()}");
            return false;
        }
    }

    public function startRelay(EttvSlot $slot, string $serverIp, int $serverPort, string $password = ''): bool
    {
        $vars = [
            'ETTV_MODE' => 'relay',
            'ETTV_RELAY_IP' => $serverIp,
            'ETTV_RELAY_PORT' => (string) $serverPort,
            'ETTV_RELAY_PASS' => $password,
            'ENGINE' => 'etlded',
            'SV_HOSTNAME' => '^3[Wolffiles.eu] ^7ETTV Live',
        ];

        if (!$this->setVariables($slot, $vars)) {
            return false;
        }

        $this->killServer($slot);
        sleep(2);
        return $this->startServer($slot);
    }

    public function startDemo(EttvSlot $slot, string $demoName = 'demo0000'): bool
    {
        $vars = [
            'ETTV_MODE' => 'demo',
            'DEMO_NAME' => $demoName,
            'ENGINE' => 'etlded',
            'ETTV_RELAY_IP' => '',
            'SV_HOSTNAME' => '^3[Wolffiles.eu] ^7ETTV Demo',
        ];

        if (!$this->setVariables($slot, $vars)) {
            return false;
        }

        $this->killServer($slot);
        sleep(2);
        return $this->startServer($slot);
    }

    public function uploadDemo(EttvSlot $slot, string $localPath, string $demoName = 'demo0000'): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/client/servers/{$slot->pterodactyl_uuid}/files/upload");
            if (!$response->successful()) return false;
            $uploadUrl = $response->json('attributes.url');
            $response = Http::attach('files', file_get_contents($localPath), "{$demoName}.tv_84")
                ->post($uploadUrl . '&directory=/etpro/demos');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Demo upload failed: {$e->getMessage()}");
            return false;
        }
    }

    public function getServerStatus(EttvSlot $slot): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/client/servers/{$slot->pterodactyl_uuid}/resources");
            return $response->successful() ? $response->json('attributes') : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    // ─── Server Hosting Stubs (TODO: Implement with Application API) ───

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    public function getServerResources(int $serverId): ?array
    {
        return null;
    }

    public function startHostedServer($identifier): bool
    {
        return $this->sendHostedPowerSignal($identifier, 'start');
    }

    public function stopHostedServer($identifier): bool
    {
        return $this->sendHostedPowerSignal($identifier, 'stop');
    }

    public function restartServer($identifier): bool
    {
        return $this->sendHostedPowerSignal($identifier, 'restart');
    }

    protected function sendHostedPowerSignal($identifier, string $signal): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/api/client/servers/{$identifier}/power", [
                    'signal' => $signal,
                ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Pterodactyl hosted power: {$e->getMessage()}", compact('identifier', 'signal'));
            return false;
        }
    }

    public function sendCommand($identifier, string $command): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/api/client/servers/{$identifier}/command", [
                    'command' => $command,
                ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Pterodactyl command: {$e->getMessage()}", compact('identifier', 'command'));
            return false;
        }
    }

    public function getOrCreateUser(array $data): ?array
    {
        return null;
    }

    public function getAvailableAllocation(int $nodeId): ?array
    {
        return null;
    }

    public function createServer(\App\Models\ServerOrder $order, int $userId, int $allocationId): ?array
    {
        return null;
    }

    public function suspendServer(int $serverId): bool
    {
        return false;
    }

    public function unsuspendServer(int $serverId): bool
    {
        return false;
    }

    public function deleteServer(int $serverId): bool
    {
        return false;
    }
}
