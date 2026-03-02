<?php

namespace App\Services;

use App\Models\ServerOrder;
use App\Models\ServerProduct;
use App\Models\ServerNode;
use App\Models\ServerInvoice;
use App\Models\ServerActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\HostingDiscordNotifier;

class ServerProvisioningService
{
    protected PterodactylService $pterodactyl;

    public function __construct(PterodactylService $pterodactyl)
    {
        $this->pterodactyl = $pterodactyl;
    }

    /**
     * Provision a new server after payment
     */
    public function provision(ServerOrder $order): bool
    {
        $order->update(['status' => 'provisioning']);

        try {
            // 1. Find best node
            $node = ServerNode::findBestNode($order->product);
            if (!$node) {
                Log::error('Provisioning: No available node', ['order_id' => $order->id]);
                $order->update(['status' => 'error']);
                return false;
            }

            // 2. Get or create Pterodactyl user
            $pteroUser = $this->pterodactyl->getOrCreateUser([
                'email' => $order->user->email,
                'username' => 'wf_' . $order->user->id,
                'first_name' => $order->user->name,
                'last_name' => 'Wolffiles',
                'password' => Str::random(32),
            ]);

            if (!$pteroUser) {
                Log::error('Provisioning: Failed to create Pterodactyl user', ['order_id' => $order->id]);
                $order->update(['status' => 'error']);
                return false;
            }

            // 3. Get available allocation (port)
            $allocation = $this->pterodactyl->getAvailableAllocation($node->pterodactyl_node_id);
            if (!$allocation) {
                Log::error('Provisioning: No available allocation on node', ['order_id' => $order->id, 'node' => $node->name]);
                $order->update(['status' => 'error']);
                return false;
            }

            // 4. Generate RCON password if not set
            if (empty($order->rcon_password)) {
                $order->update(['rcon_password' => Str::random(16)]);
            }

            // 5. Create server on Pterodactyl
            $order->update([
                'ip_address' => $allocation['ip'],
                'port' => $allocation['port'],
            ]);

            $server = $this->pterodactyl->createServer($order, $pteroUser['id'], $allocation['id']);
            if (!$server) {
                Log::error('Provisioning: Failed to create Pterodactyl server', ['order_id' => $order->id]);
                $order->update(['status' => 'error']);
                return false;
            }

            // 6. Update order with Pterodactyl details
            $order->update([
                'pterodactyl_server_id' => $server['id'],
                'pterodactyl_user_id' => (string) $pteroUser['id'],
                'node_id' => $node->id,
                'node' => $node->name,
                'status' => 'active',
                'last_status_check' => now(),
            ]);

            // 7. Allocate resources on node
            $node->allocate($order->product);

            // 8. Log activity
            ServerActivityLog::log($order, 'created', [
                'game' => $order->game,
                'mod' => $order->mod,
                'slots' => $order->slots,
                'node' => $node->name,
                'ip' => $order->ip_address,
                'port' => $order->port,
            ], 'system');

            // Discord notification
            HostingDiscordNotifier::serverProvisioned($order);
            Log::info('Provisioning: Server created successfully', [
                'order_id' => $order->id,
                'pterodactyl_id' => $server['id'],
                'ip' => $order->ip_address . ':' . $order->port,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Provisioning: Unexpected error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            $order->update(['status' => 'error']);
            return false;
        }
    }

    /**
     * Suspend a server (expired, not paid)
     */
    public function suspend(ServerOrder $order): bool
    {
        if (!$order->pterodactyl_server_id) return false;

        $success = $this->pterodactyl->suspendServer($order->pterodactyl_server_id);
        if ($success) {
            $order->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);
            ServerActivityLog::log($order, 'suspended', ['reason' => 'Payment expired'], 'system');
            HostingDiscordNotifier::serverSuspended($order);
        }
        return $success;
    }

    /**
     * Unsuspend/reactivate a server (payment received)
     */
    public function unsuspend(ServerOrder $order): bool
    {
        if (!$order->pterodactyl_server_id) return false;

        $success = $this->pterodactyl->unsuspendServer($order->pterodactyl_server_id);
        if ($success) {
            $order->update([
                'status' => 'active',
                'suspended_at' => null,
            ]);
            ServerActivityLog::log($order, 'renewed', ['new_paid_until' => $order->paid_until], 'system');
        }
        return $success;
    }

    /**
     * Terminate a server (30 days after expiry)
     */
    public function terminate(ServerOrder $order): bool
    {
        if ($order->pterodactyl_server_id) {
            $this->pterodactyl->deleteServer($order->pterodactyl_server_id);
        }

        // Deallocate node resources
        if ($order->node_id && $order->product) {
            $order->node->deallocate($order->product);
        }

        $order->update([
            'status' => 'terminated',
            'terminated_at' => now(),
        ]);

        ServerActivityLog::log($order, 'terminated', ['reason' => '30 days after expiry'], 'system');
        HostingDiscordNotifier::serverTerminated($order);

        return true;
    }

    /**
     * Renew an order — extend paid_until
     */
    public function renew(ServerOrder $order, string $period = null): ServerInvoice
    {
        $period = $period ?? $order->billing_period;
        $price = $order->product->getPriceForPeriod($period);

        $periodStart = $order->paid_until > now() ? $order->paid_until : now();
        $periodEnd = match($period) {
            'daily' => $periodStart->copy()->addDay(),
            'weekly' => $periodStart->copy()->addWeek(),
            'monthly' => $periodStart->copy()->addMonth(),
            'quarterly' => $periodStart->copy()->addMonths(3),
            default => $periodStart->copy()->addMonth(),
        };

        $invoice = ServerInvoice::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'amount' => $price,
            'currency' => 'EUR',
            'period' => $period,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'pending',
        ]);

        return $invoice;
    }

    /**
     * After payment confirmation — activate/extend
     */
    public function confirmPayment(ServerInvoice $invoice, string $transactionId, string $method = 'paypal'): bool
    {
        $invoice->update([
            'status' => 'paid',
            'payment_method' => $method,
            'payment_transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);

        $order = $invoice->order;
        $order->update([
            'paid_until' => $invoice->period_end,
            'price_paid' => $invoice->amount,
            'billing_period' => $invoice->period,
        ]);

        // If server was suspended, reactivate
        /** @var \App\Models\ServerOrder $order */
        if ($order->status === 'suspended') {
            $this->unsuspend($order);
        }

        // If new order (pending), provision
        if ($order->status === 'pending') {
            $this->provision($order);
        }

        return true;
    }
}
