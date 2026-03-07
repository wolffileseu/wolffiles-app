<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        return view('frontend.notifications.index', compact('notifications'));
    }

    public function markRead(string $id)
    {
        $notification = auth()->user()->notifications()->find($id);
        
        if (!$notification) {
            return redirect()->route('home');
        }
        
        $notification->markAsRead();
        $url = $notification->data['url'] ?? route('home');

        // Prevent open redirect - only allow internal URLs
        $parsed = parse_url($url);
        $appHost = parse_url(config('app.url', ''), PHP_URL_HOST);
        if (isset($parsed['host']) && $parsed['host'] !== $appHost) {
            $url = route('home');
        }

        return redirect($url);
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', __('messages.all_read'));
    }
}
