<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Route names that are intentionally embeddable on other origins.
     * These must NOT have X-Frame-Options set, otherwise browsers block the iframe.
     */
    private const EMBEDDABLE_ROUTES = [
        'tracker.server.embed',
        // 'tracker.player.embed',  // reserve for later
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $routeName = $request->route()?->getName();
        $isEmbeddable = $routeName !== null && in_array($routeName, self::EMBEDDABLE_ROUTES, true);

        // Set X-Frame-Options only for non-embeddable routes
        if (!$isEmbeddable) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
