<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiscordController extends Controller
{
    /**
     * Redirect to Discord OAuth
     */
    public function redirect()
    {
        $state = Str::random(40);
        session(['discord_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => config('services.discord.client_id'),
            'redirect_uri' => config('services.discord.redirect'),
            'response_type' => 'code',
            'scope' => 'identify email',
            'state' => $state,
        ]);

        return redirect('https://discord.com/api/oauth2/authorize?' . $params);
    }

    /**
     * Handle Discord callback
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('login')->with('error', 'Discord login cancelled.');
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('login')->with('error', 'Invalid Discord response.');
        }

        // Verify OAuth state to prevent CSRF attacks
        $expectedState = session()->pull('discord_oauth_state');
        if (!$expectedState || $request->get('state') !== $expectedState) {
            return redirect()->route('login')->with('error', 'Invalid OAuth state. Please try again.');
        }

        // Exchange code for token
        $tokenResponse = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => config('services.discord.client_id'),
            'client_secret' => config('services.discord.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.discord.redirect'),
        ]);

        if (!$tokenResponse->ok()) {
            return redirect()->route('login')->with('error', 'Failed to authenticate with Discord.');
        }

        $accessToken = $tokenResponse->json('access_token');

        // Get Discord user info
        $userResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://discord.com/api/users/@me');

        if (!$userResponse->ok()) {
            return redirect()->route('login')->with('error', 'Failed to get Discord user info.');
        }

        $discordUser = $userResponse->json();
        $discordId = $discordUser['id'];
        $discordUsername = $discordUser['username'];
        $discordEmail = $discordUser['email'] ?? null;
        $discordAvatar = isset($discordUser['avatar'])
            ? "https://cdn.discordapp.com/avatars/{$discordId}/{$discordUser['avatar']}.png"
            : null;

        // If user is logged in → link Discord account
        if (Auth::check()) {
            Auth::user()->update([
                'discord_id' => $discordId,
                'discord_username' => $discordUsername,
            ]);

            return redirect()->route('profile.settings')->with('success', 'Discord account connected!');
        }

        // Find existing user by Discord ID
        $user = User::where('discord_id', $discordId)->first();

        // Or find by email
        if (!$user && $discordEmail) {
            $user = User::where('email', $discordEmail)->first();
            if ($user) {
                $user->update([
                    'discord_id' => $discordId,
                    'discord_username' => $discordUsername,
                ]);
            }
        }

        // Create new user
        if (!$user) {
            if (!$discordEmail) {
                return redirect()->route('login')->with('error', 'Discord account has no email. Please register with email first, then connect Discord.');
            }

            $user = User::create([
                'name' => $discordUsername,
                'email' => $discordEmail,
                'password' => bcrypt(Str::random(32)),
                'discord_id' => $discordId,
                'discord_username' => $discordUsername,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('user');
        }

        if (!$user->is_active) {
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Login
        Auth::login($user, true);
        $user->update(['last_login_at' => now()]);

        ActivityLogger::login($user);

        return redirect()->intended(route('home'))->with('success', 'Logged in via Discord!');
    }

    /**
     * Disconnect Discord account
     */
    public function disconnect()
    {
        auth()->user()->update([
            'discord_id' => null,
            'discord_username' => null,
        ]);

        return redirect()->route('profile.settings')->with('success', 'Discord account disconnected.');
    }
}
