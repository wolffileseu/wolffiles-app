<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Services\ActivityLogger;

class ContactController extends Controller
{
    public function show()
    {
        return view('frontend.contact');
    }

    public function send(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'honeypot' => 'size:0', // spam protection
        ]);

        // Sanitize inputs to prevent email header injection
        $safeName = str_replace(["\r", "\n", "\t"], ' ', $request->name);
        $safeSubject = str_replace(["\r", "\n", "\t"], ' ', $request->subject);
        $safeMessage = $request->message;

        Mail::raw(
            "From: {$safeName} ({$request->email})\n\nSubject: {$safeSubject}\n\n{$safeMessage}",
            function ($mail) use ($request, $safeName, $safeSubject) {
                $mail->to(config('mail.from.address', 'admin@wolffiles.eu'))
                    ->replyTo($request->email, $safeName)
                    ->subject('[Wolffiles.eu Contact] ' . $safeSubject);
            }
        );

        ActivityLogger::contactSubmit($request->subject, $request->name);

        return back()->with('success', __('messages.contact_sent'));
    }
}
