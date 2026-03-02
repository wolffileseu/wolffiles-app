<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EasterEggController extends Controller
{
    /**
     * Award the "Secret Agent" badge to the authenticated user.
     *
     * POST /easter-egg/complete
     */
    public function complete(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Du musst eingeloggt sein um das Badge zu erhalten.',
            ], 401);
        }

        // Badge aus der Datenbank holen
        $badge = Badge::where('slug', 'secret-agent')->first();

        if (! $badge) {
            return response()->json([
                'success' => false,
                'message' => 'Badge nicht gefunden.',
            ], 404);
        }

        // Prüfen ob User das Badge bereits hat
        if ($user->badges()->where('badge_id', $badge->id)->exists()) {
            return response()->json([
                'success' => true,
                'message' => 'Du hast dieses Badge bereits!',
                'already_awarded' => true,
            ], 409);
        }

        // Badge vergeben
        $user->badges()->attach($badge->id, [
            'awarded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Achievement unlocked: Secret Agent! 🎮',
            'badge' => [
                'name' => $badge->name,
                'slug' => $badge->slug,
                'icon' => $badge->icon,
                'description' => $badge->description,
            ],
        ]);
    }
}
