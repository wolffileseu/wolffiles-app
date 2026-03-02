<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class CreditsController extends Controller
{
    public function index()
    {
        $isActive = Setting::get('credits_page_active', true);
        if (!$isActive) abort(404);

        $entries = Setting::get('credits_entries', []);
        $headerText = Setting::get('credits_header_text', '');
        $footerText = Setting::get('credits_footer_text', '');

        // Gruppiere nach Kategorie
        $grouped = collect(is_array($entries) ? $entries : [])
            ->groupBy('category')
            ->sortKeysUsing(function ($a, $b) {
                $order = ['team', 'special', 'contributor', 'donor', 'community', 'project'];
                return array_search($a, $order) - array_search($b, $order);
            });

        $categoryLabels = [
            'team' => ['label' => 'Team', 'icon' => '👥', 'description' => 'Die Menschen hinter Wolffiles.eu'],
            'special' => ['label' => 'Besonderer Dank', 'icon' => '⭐', 'description' => 'Herausragende Unterstützung'],
            'contributor' => ['label' => 'Contributors', 'icon' => '🛠️', 'description' => 'Code, Content und mehr'],
            'donor' => ['label' => 'Sponsoren & Spender', 'icon' => '💰', 'description' => 'Finanzielle Unterstützung'],
            'community' => ['label' => 'Community', 'icon' => '🌍', 'description' => 'Aktive Community-Mitglieder'],
            'project' => ['label' => 'Projekte & Tools', 'icon' => '📦', 'description' => 'Open-Source Projekte die wir nutzen'],
        ];

        $seo = ['title' => 'Credits - Wolffiles.eu', 'description' => 'Die Menschen und Projekte hinter Wolffiles.eu'];

        return view('frontend.credits', compact('grouped', 'categoryLabels', 'headerText', 'footerText', 'seo'));
    }
}
