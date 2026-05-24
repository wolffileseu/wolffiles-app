<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\SocialMedia\SocialMediaService;
use App\Models\DonationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DonationController extends Controller
{
    public function index()
    {
        $monthlyGoal = (float) DonationSetting::get('monthly_goal', 50);
        $monthlyTotal = Donation::completed()->thisMonth()->sum('amount');
        $monthlyPercent = $monthlyGoal > 0 ? min(100, round(($monthlyTotal / $monthlyGoal) * 100)) : 0;

        $recentDonors = Donation::visible()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $topDonors = Donation::visible()
            ->where('is_anonymous', false)
            ->selectRaw('COALESCE(donor_name, "Anonymous") as name, user_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('donor_name', 'user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $totalAllTime = Donation::completed()->sum('amount');
        $totalDonors = Donation::completed()->distinct('donor_email')->count('donor_email')
            + Donation::completed()->whereNull('donor_email')->count();

        $paypalEmail = DonationSetting::get('paypal_email', '');
        $paypalEnabled = DonationSetting::get('paypal_enabled', '1') === '1';
        $donationMessage = DonationSetting::get('donation_message', '');

        return view('frontend.donate', compact(
            'monthlyGoal', 'monthlyTotal', 'monthlyPercent',
            'recentDonors', 'topDonors', 'totalAllTime', 'totalDonors',
            'paypalEmail', 'paypalEnabled', 'donationMessage'
        ));
    }

    /**
     * PayPal IPN Handler
     */
    public function paypalIpn(Request $request)
    {
        $data = $request->all();

        // Verify with PayPal
        try {
            $response = Http::asForm()->post('https://ipnpb.paypal.com/cgi-bin/webscr', array_merge(
                ['cmd' => '_notify-validate'],
                $data
            ));

            if ($response->body() !== 'VERIFIED') {
                Log::warning('PayPal IPN: Not verified', $data);
                return response('INVALID', 400);
            }
        } catch (\Exception $e) {
            Log::error('PayPal IPN verification failed: ' . $e->getMessage());
            return response('ERROR', 500);
        }

        // Only process completed payments
        if (($data['payment_status'] ?? '') !== 'Completed') {
            return response('OK');
        }

        $txnId = $data['txn_id'] ?? null;
        if (!$txnId || Donation::where('transaction_id', $txnId)->exists()) {
            return response('DUPLICATE');
        }

        // Extract donor info
        $amount = (float) ($data['mc_gross'] ?? 0);
        $currency = $data['mc_currency'] ?? 'EUR';
        $email = $data['payer_email'] ?? null;
        $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $message = $data['memo'] ?? ($data['custom'] ?? null);

        // Try to match to user
        $user = null;
        if ($email) {
            $user = \App\Models\User::where('email', $email)->first();
        }

        $donation = Donation::create([
            'user_id' => $user?->id,
            'donor_name' => $name ?: null,
            'donor_email' => $email,
            'amount' => $amount,
            'currency' => $currency,
            'message' => $message,
            'source' => 'paypal',
            'transaction_id' => $txnId,
            'status' => 'completed',
            'meta' => $data,
        ]);

        // Notifications
        app(\App\Services\DonationDiscordService::class)->notify($donation);
        $this->notifyEmail($donation);
        app(SocialMediaService::class)->broadcastDonation($donation);

        Log::info("Donation received: €{$amount} from {$name} ({$email})");
        return response('OK');
    }
    private function notifyEmail(Donation $donation): void
    {
        $email = DonationSetting::get('notification_email');
        if (!$email) return;

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "New donation received!\n\n" .
                "Donor: {$donation->display_name}\n" .
                "Amount: €{$donation->amount}\n" .
                "Source: {$donation->source}\n" .
                "Message: " . ($donation->message ?: "None") . "\n" .
                "Transaction: {$donation->transaction_id}\n\n" .
                "View in admin: " . url("/admin/donations"),
                function ($msg) use ($email, $donation) {
                    $msg->to($email)
                        ->subject("💰 New Donation: €{$donation->amount} from {$donation->display_name}");
                }
            );
        } catch (\Exception $e) {
            Log::warning('Donation email notification failed: ' . $e->getMessage());
        }
    }


}
