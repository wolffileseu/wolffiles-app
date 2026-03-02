<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ServerProduct;
use App\Models\ServerOrder;
use App\Models\ServerInvoice;
use App\Models\ServerNode;
use App\Models\ServerActivityLog;
use App\Services\PterodactylService;
use App\Services\ServerProvisioningService;
use App\Services\ActivityLogger;
use App\Services\HostingDiscordNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HostingController extends Controller
{
    // ==========================================
    // PUBLIC: Landing Page + Bestellseite
    // ==========================================

    public function index()
    {
        $products = ServerProduct::active()->orderBy('sort_order')->get();
        $seo = ['title' => 'ET Server Hosting - Wolffiles.eu', 'description' => 'Wolfenstein: Enemy Territory & RtCW Gameserver mieten. Ab 0,50€/Slot/Monat. FastDL, DDoS Schutz, Web Panel.'];

        return view('frontend.hosting.index', compact('products', 'seo'));
    }

    public function configure(ServerProduct $product)
    {
        if (!$product->is_active) abort(404);

        $seo = ['title' => $product->name . ' mieten - Wolffiles.eu', 'description' => $product->description];

        return view('frontend.hosting.configure', compact('product', 'seo'));
    }

    public function calculatePrice(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:server_products,id',
            'slots' => 'required|integer|min:2|max:128',
        ]);

        $product = ServerProduct::findOrFail($request->product_id);
        $slots = max($product->min_slots, min($product->max_slots, $request->slots));

        return response()->json([
            'slots' => $slots,
            'prices' => $product->calculatePrices($slots),
            'resources' => $product->calculateResources($slots),
        ]);
    }

    // ==========================================
    // AUTH: Checkout + Bestellung
    // ==========================================

    public function checkout(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:server_products,id',
            'slots' => 'required|integer|min:2|max:128',
            'period' => 'required|in:daily,weekly,monthly,quarterly',
            'server_name' => 'required|string|max:64',
            'mod' => 'required|string|max:32',
        ]);

        $product = ServerProduct::findOrFail($request->product_id);
        $slots = max($product->min_slots, min($product->max_slots, $request->slots));
        $price = $product->calculatePrice($slots, $request->period);

        $order = ServerOrder::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'server_name' => $request->server_name,
            'game' => $product->game,
            'mod' => $request->mod,
            'slots' => $slots,
            'status' => 'pending',
            'billing_period' => $request->period,
            'price_paid' => $price,
            'rcon_password' => $request->rcon_password ?: Str::random(16),
            'server_password' => $request->server_password ?: null,
        ]);

        $periodEnd = match($request->period) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            default => now()->addMonth(),
        };

        $invoice = ServerInvoice::create([
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'amount' => $price,
            'currency' => 'EUR',
            'period' => $request->period,
            'period_start' => now(),
            'period_end' => $periodEnd,
            'status' => 'pending',
        ]);

        // Discord notification
        HostingDiscordNotifier::serverOrdered($order, $invoice);
        ServerActivityLog::log($order, 'created', [
            'slots' => $slots,
            'mod' => $request->mod,
            'period' => $request->period,
            'price' => $price,
        ]);

        return redirect()->route('hosting.payment', $order);
    }

    public function payment(ServerOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless(in_array($order->status, ['pending', 'suspended']), 404);

        $invoice = $order->invoices()->where('status', 'pending')->latest()->first();
        if (!$invoice) {
            return redirect()->route('hosting.dashboard')->with('error', 'Keine offene Rechnung gefunden.');
        }

        $paypalEmail = \App\Models\DonationSetting::get('paypal_email', '');

        return view('frontend.hosting.payment', compact('order', 'invoice', 'paypalEmail'));
    }

    public function paymentSuccess(Request $request, ServerOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        // PayPal leitet hier hin nach erfolgreicher Zahlung
        // Die eigentliche Verifizierung läuft über IPN
        // Hier zeigen wir nur eine Bestätigung

        if ($order->status === 'active') {
            return redirect()->route('hosting.server', $order)
                ->with('success', 'Server ist aktiv! Viel Spaß beim Spielen.');
        }

        // IPN noch nicht angekommen — zeige Wartenseite
        return redirect()->route('hosting.dashboard')
            ->with('success', 'Zahlung wird verarbeitet. Dein Server wird in Kürze erstellt.');
    }

    /**
     * PayPal IPN Handler für Hosting-Zahlungen
     */
    public function paypalIpn(Request $request)
    {
        $data = $request->all();

        // Verify with PayPal
        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post('https://ipnpb.paypal.com/cgi-bin/webscr', array_merge(
                    ['cmd' => '_notify-validate'],
                    $data
                ));

            if ($response->body() !== 'VERIFIED') {
                \Illuminate\Support\Facades\Log::warning('Hosting PayPal IPN: Not verified', $data);
                return response('INVALID', 400);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Hosting PayPal IPN verification failed: ' . $e->getMessage());
            return response('ERROR', 500);
        }

        // Only process completed payments
        if (($data['payment_status'] ?? '') !== 'Completed') {
            \Illuminate\Support\Facades\Log::info('Hosting IPN: Non-completed status', ['status' => $data['payment_status'] ?? 'unknown']);
            return response('OK');
        }

        // Parse custom data
        $custom = json_decode($data['custom'] ?? '{}', true);
        if (empty($custom['order_id']) || ($custom['type'] ?? '') !== 'hosting') {
            \Illuminate\Support\Facades\Log::warning('Hosting IPN: Invalid custom data', $data);
            return response('INVALID_CUSTOM', 400);
        }

        $orderId = $custom['order_id'];
        $invoiceId = $custom['invoice_id'] ?? null;
        $txnId = $data['txn_id'] ?? null;

        // Prevent duplicate processing
        if ($txnId && ServerInvoice::where('payment_transaction_id', $txnId)->exists()) {
            return response('DUPLICATE');
        }

        $order = ServerOrder::find($orderId);
        if (!$order) {
            \Illuminate\Support\Facades\Log::error('Hosting IPN: Order not found', ['order_id' => $orderId]);
            return response('NOT_FOUND', 404);
        }

        // Find invoice
        $invoice = $invoiceId ? ServerInvoice::find($invoiceId) : $order->invoices()->where('status', 'pending')->latest()->first();
        if (!$invoice) {
            \Illuminate\Support\Facades\Log::error('Hosting IPN: Invoice not found', ['order_id' => $orderId]);
            return response('NO_INVOICE', 404);
        }

        // Verify amount
        $paidAmount = (float) ($data['mc_gross'] ?? 0);
        if ($paidAmount < (float) $invoice->amount) {
            \Illuminate\Support\Facades\Log::warning('Hosting IPN: Amount mismatch', [
                'expected' => $invoice->amount, 'received' => $paidAmount,
            ]);
            return response('AMOUNT_MISMATCH', 400);
        }

        // Mark invoice as paid
        $invoice->update([
            'status' => 'paid',
            'payment_method' => 'paypal',
            'payment_transaction_id' => $txnId,
            'payment_details' => $data,
            'paid_at' => now(),
        ]);

        // Update order paid_until
        $order->update([
            'paid_until' => $invoice->period_end,
            'price_paid' => $paidAmount,
        ]);

        // Provision or unsuspend
        $provisioning = app(ServerProvisioningService::class);

        if ($order->status === 'pending') {
            $provisioning->provision($order);
        } elseif ($order->status === 'suspended') {
            $provisioning->unsuspend($order);
        }

        ServerActivityLog::log($order, 'renewed', [
            'amount' => $paidAmount,
            'txn_id' => $txnId,
            'paid_until' => $invoice->period_end->toDateString(),
        ], 'system');

        // Discord notification
        HostingDiscordNotifier::paymentReceived($order, $paidAmount, $txnId);
        \Illuminate\Support\Facades\Log::info('Hosting IPN: Payment processed', [
            'order_id' => $order->id,
            'amount' => $paidAmount,
            'txn_id' => $txnId,
        ]);

        return response('OK');
    }

    // ==========================================
    // AUTH: User Dashboard
    // ==========================================

    public function dashboard()
    {
        $orders = ServerOrder::where('user_id', auth()->id())
            ->whereNotIn('status', ['terminated'])
            ->with(['product', 'node'])
            ->orderByDesc('created_at')
            ->get();

        $seo = ['title' => 'Meine Server - Wolffiles.eu', 'description' => 'Deine Gameserver verwalten'];

        return view('frontend.hosting.dashboard', compact('orders', 'seo'));
    }

    public function serverDetail(ServerOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $activities = $order->activityLogs()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $backups = $order->backups()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $seo = ['title' => $order->server_name . ' - Wolffiles.eu', 'description' => 'Server Dashboard'];

        return view('frontend.hosting.server-detail', compact('order', 'activities', 'backups', 'seo'));
    }

    // ==========================================
    // AUTH: Server Controls
    // ==========================================

    public function serverAction(Request $request, ServerOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->isActive(), 403);

        $request->validate(['action' => 'required|in:start,stop,restart']);

        $ptero = app(PterodactylService::class);
        $identifier = $order->pterodactyl_server_id;
        $action = $request->action;

        $success = match($action) {
            'start' => $ptero->restartServer($identifier),
            'stop' => $ptero->restartServer($identifier),
            'restart' => $ptero->restartServer($identifier),
            default => false,
        };

        if ($success) {
            ServerActivityLog::log($order, $action);
        }

        return back()->with(
            $success ? 'success' : 'error',
            $success ? "Server wird {$action}ed..." : 'Aktion fehlgeschlagen.'
        );
    }

    public function sendCommand(Request $request, ServerOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->isActive(), 403);

        $request->validate(['command' => 'required|string|max:255']);

        $ptero = app(PterodactylService::class);
        $success = $ptero->sendCommand($order->pterodactyl_server_id, $request->command);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Command gesendet' : 'Command fehlgeschlagen'
        );
    }

    // ==========================================
    // AUTH: Renewal
    // ==========================================

    public function renew(ServerOrder $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $product = $order->product;
        $prices = $product->calculatePrices($order->slots);

        return view('frontend.hosting.renew', compact('order', 'prices'));
    }
}
