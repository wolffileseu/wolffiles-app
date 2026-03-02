<x-layouts.app>
<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">
            ❤️ <span class="text-amber-500">{{ __('messages.credits_title') }}</span>
        </h1>
        @if($headerText)
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">{{ $headerText }}</p>
        @endif
    </div>

    @foreach($grouped as $category => $people)
        @php
            $catLabels = [
                'team' => ['label' => __('messages.credits_team'), 'icon' => '👥', 'desc' => __('messages.credits_team_desc')],
                'special' => ['label' => __('messages.credits_special'), 'icon' => '⭐', 'desc' => __('messages.credits_special_desc')],
                'contributor' => ['label' => __('messages.credits_contributors'), 'icon' => '🛠️', 'desc' => __('messages.credits_contributors_desc')],
                'donor' => ['label' => __('messages.credits_donors'), 'icon' => '💰', 'desc' => __('messages.credits_donors_desc')],
                'community' => ['label' => __('messages.credits_community'), 'icon' => '🌍', 'desc' => __('messages.credits_community_desc')],
                'project' => ['label' => __('messages.credits_projects'), 'icon' => '📦', 'desc' => __('messages.credits_projects_desc')],
            ];
            $cat = $catLabels[$category] ?? ['label' => $category, 'icon' => '🏷️', 'desc' => ''];
        @endphp

        <div class="mb-12">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">{{ $cat['icon'] }}</span>
                <h2 class="text-2xl font-bold text-white">{{ $cat['label'] }}</h2>
            </div>
            @if($cat['desc'])
                <p class="text-gray-500 text-sm mb-6 ml-10">{{ $cat['desc'] }}</p>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($people as $person)
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-5 hover:border-amber-500/30 transition-all">
                    <div class="flex items-start gap-4">
                        @if(!empty($person['avatar_url']))
                            <img src="{{ $person['avatar_url'] }}" alt="{{ $person['name'] }}" class="w-12 h-12 rounded-full object-cover flex-shrink-0 border-2 border-gray-600">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center flex-shrink-0 text-lg font-bold text-amber-500">
                                {{ strtoupper(substr($person['name'] ?? '?', 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                @if(!empty($person['url']))
                                    <a href="{{ $person['url'] }}" target="_blank" rel="noopener" class="font-bold text-white hover:text-amber-400 transition-colors truncate">{{ $person['name'] }}</a>
                                    <svg class="w-3 h-3 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                @else
                                    <span class="font-bold text-white truncate">{{ $person['name'] }}</span>
                                @endif
                            </div>
                            @if(!empty($person['role']))
                                <p class="text-amber-500/80 text-sm">{{ $person['role'] }}</p>
                            @endif
                            @if(!empty($person['description']))
                                <p class="text-gray-400 text-sm mt-1">{{ $person['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if($grouped->isEmpty())
        <div class="text-center py-12">
            <span class="text-6xl block mb-4">🏗️</span>
            <p class="text-gray-400">{{ __('messages.credits_being_setup') }}</p>
        </div>
    @endif

    @if($footerText)
        <div class="text-center mt-12 pt-8 border-t border-gray-700">
            <p class="text-gray-400 text-lg">{{ $footerText }}</p>
        </div>
    @endif
</div>
</x-layouts.app>
