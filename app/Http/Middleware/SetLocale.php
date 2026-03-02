<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Locale priority:
     * 1. URL param ?lang=de (explicit switch)
     * 2. User DB preference (logged in)
     * 3. Cookie (returning guest)
     * 4. Session
     * 5. Browser Accept-Language (auto-detect)
     * 6. Default (en)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys(config('languages', ['en' => [], 'de' => []]));
        $locale = null;

        // 1. Explicit switch via URL param
        if ($request->has('lang') && in_array($request->get('lang'), $supportedLocales)) {
            $locale = $request->get('lang');

            cookie()->queue('locale', $locale, 60 * 24 * 365);
            session(['locale' => $locale]);

            if (auth()->check() && auth()->user()->locale !== $locale) {
                auth()->user()->update(['locale' => $locale]);
            }
        }

        // 2. User DB preference
        if (!$locale && auth()->check() && in_array(auth()->user()->locale, $supportedLocales)) {
            $locale = auth()->user()->locale;
        }

        // 3. Cookie
        if (!$locale && $request->cookie('locale') && in_array($request->cookie('locale'), $supportedLocales)) {
            $locale = $request->cookie('locale');
        }

        // 4. Session
        if (!$locale && session('locale') && in_array(session('locale'), $supportedLocales)) {
            $locale = session('locale');
        }

        // 5. Browser Accept-Language auto-detect
        if (!$locale) {
            $browserLocale = $request->getPreferredLanguage($supportedLocales);
            if ($browserLocale && in_array($browserLocale, $supportedLocales)) {
                $locale = $browserLocale;
                cookie()->queue('locale', $locale, 60 * 24 * 365);
                session(['locale' => $locale]);
            }
        }

        // 6. Default
        $locale = $locale ?? config('app.locale', 'en');

        app()->setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
