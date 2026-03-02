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

        Mail::raw(
            "From: {$request->name} ({$request->email})\n\nSubject: {$request->subject}\n\n{$request->message}",
            function ($mail) use ($request) {
                $mail->to(config('mail.from.address', 'admin@wolffiles.eu'))
                    ->replyTo($request->email, $request->name)
                    ->subject('[Wolffiles.eu Contact] ' . $request->subject);
            }
        );

        ActivityLogger::contactSubmit($request->subject, $request->name);

        return back()->with('success', __('messages.contact_sent'));
    }
}
