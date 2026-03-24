<?php
namespace App\Http\Middleware;

use App\Models\ClanApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateClanApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Clan-Api-Key') ?? $request->query('api_key');

        if (!$key) {
            return response()->json(['error' => 'API key missing'], 401);
        }

        $apiKey = ClanApiKey::with('clan')
            ->where('key', $key)
            ->first();

        if (!$apiKey || !$apiKey->isValid()) {
            return response()->json(['error' => 'Invalid or inactive API key'], 403);
        }

        if (!$apiKey->clan->is_active) {
            return response()->json(['error' => 'Clan is deactivated'], 403);
        }

        $apiKey->markUsed();

        $request->merge(['_clan'     => $apiKey->clan]);
        $request->merge(['_clan_key' => $apiKey]);

        return $next($request);
    }
}
