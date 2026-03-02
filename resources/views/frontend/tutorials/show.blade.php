<x-layouts.app :title="$tutorial->title">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('tutorials.index') }}" class="hover:text-amber-400">Tutorials</a>
            @if($tutorial->category)
                / <a href="{{ route('tutorials.index', ['category' => $tutorial->category->slug]) }}" class="hover:text-amber-400">{{ $tutorial->category->name }}</a>
            @endif
            / <span class="text-gray-300">{{ $tutorial->title }}</span>
        </nav>

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-xs px-2 py-0.5 rounded font-medium
                    {{ $tutorial->difficulty === 'beginner' ? 'bg-green-900/50 text-green-400' : '' }}
                    {{ $tutorial->difficulty === 'intermediate' ? 'bg-amber-900/50 text-amber-400' : '' }}
                    {{ $tutorial->difficulty === 'advanced' ? 'bg-red-900/50 text-red-400' : '' }}">
                    {{ $tutorial->difficulty === 'beginner' ? '🟢 Anfänger' : ($tutorial->difficulty === 'intermediate' ? '🟡 Fortgeschritten' : '🔴 Experte') }}
                </span>
                @if($tutorial->estimated_minutes)
                    <span class="text-gray-500 text-sm">⏱ {{ $tutorial->estimated_minutes }} Minuten</span>
                @endif
                @if($tutorial->is_featured)
                    <span class="text-amber-400 text-sm">⭐ Featured</span>
                @endif
            </div>

            <h1 class="text-3xl font-bold text-white mb-3">{{ $tutorial->title }}</h1>

            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <span>Von <strong class="text-gray-300">{{ $tutorial->user?->name }}</strong></span>
                <span>{{ $tutorial->published_at?->format('d.m.Y') }}</span>
                <span>👁 {{ $tutorial->view_count }} Views</span>
                @if($tutorial->helpful_count + $tutorial->not_helpful_count > 0)
                    <span>👍 {{ $tutorial->helpful_percentage }}% hilfreich</span>
                @endif
                @if($tutorial->tags)
                    <div class="flex gap-2">
                        @foreach($tutorial->tags as $tag)
                            <a href="{{ route('tutorials.index', ['tag' => $tag]) }}" class="text-amber-400 hover:underline text-xs">#{{ $tag }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Prerequisites --}}
        @if($tutorial->prerequisites)
            <div class="bg-blue-900/20 border border-blue-800 rounded-lg p-4 mb-6">
                <h3 class="text-blue-400 font-semibold text-sm mb-1">📋 Voraussetzungen</h3>
                <p class="text-gray-300 text-sm">{{ $tutorial->prerequisites }}</p>
            </div>
        @endif

        {{-- Series Navigation --}}
        @if($tutorial->is_series && $tutorial->seriesParts->isNotEmpty())
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-6">
                <h3 class="text-amber-400 font-semibold text-sm mb-3">📚 Tutorial-Serie</h3>
                <div class="space-y-1">
                    @foreach($tutorial->seriesParts as $part)
                        <a href="{{ route('tutorials.show', $part->slug) }}"
                           class="block px-3 py-2 rounded text-sm {{ $part->id === $tutorial->id ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white' }}">
                            Teil {{ $part->series_order }}: {{ $part->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        @elseif($tutorial->series_parent_id && $tutorial->seriesParent)
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-6">
                <h3 class="text-amber-400 font-semibold text-sm mb-3">📚 Teil einer Serie: {{ $tutorial->seriesParent->title }}</h3>
                <div class="space-y-1">
                    @foreach($tutorial->seriesParent->seriesParts as $part)
                        <a href="{{ route('tutorials.show', $part->slug) }}"
                           class="block px-3 py-2 rounded text-sm {{ $part->id === $tutorial->id ? 'bg-gray-700 text-white font-medium' : 'text-gray-400 hover:text-white' }}">
                            Teil {{ $part->series_order }}: {{ $part->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Video (YouTube or S3) --}}
        @if($tutorial->youtube_embed_url)
            <div class="mb-8 rounded-lg overflow-hidden" style="aspect-ratio: 16/9;">
                <iframe src="{{ $tutorial->youtube_embed_url }}" frameborder="0"
                        class="w-full h-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
        @elseif($tutorial->video_path)
            <div class="mb-8 rounded-lg overflow-hidden bg-black">
                <video controls class="w-full" preload="metadata">
                    <source src="{{ Storage::disk('s3')->url($tutorial->video_path) }}" type="video/mp4">
                    Dein Browser unterstützt das Video-Format nicht.
                </video>
            </div>
        @endif

        {{-- Main Content --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 mb-8">
            <div class="prose prose-invert max-w-none prose-headings:text-amber-400 prose-a:text-amber-400 prose-code:text-green-400 prose-img:rounded-lg prose-pre:bg-gray-900">
                {!! $tutorial->content !!}
            </div>
        </div>

        {{-- Steps --}}
        @if($tutorial->steps->isNotEmpty())
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">📝 Schritt für Schritt</h2>
                <div class="space-y-6">
                    @foreach($tutorial->steps as $step)
                        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6" id="step-{{ $step->step_number }}">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-amber-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                                    {{ $step->step_number }}
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-white mb-3">{{ $step->title }}</h3>

                                    @if($step->image_url)
                                        <img src="{{ $step->image_url }}" alt="Step {{ $step->step_number }}" class="rounded-lg mb-4 max-w-full border border-gray-600">
                                    @endif

                                    @if($step->video_url)
                                        @php
                                            preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $step->video_url, $m);
                                            $stepYtId = $m[1] ?? null;
                                        @endphp
                                        @if($stepYtId)
                                            <div class="mb-4 rounded-lg overflow-hidden" style="aspect-ratio: 16/9;">
                                                <iframe src="https://www.youtube-nocookie.com/embed/{{ $stepYtId }}" frameborder="0" class="w-full h-full" allowfullscreen></iframe>
                                            </div>
                                        @endif
                                    @endif

                                    <div class="prose prose-invert prose-sm max-w-none prose-code:text-green-400">
                                        {!! $step->content !!}
                                    </div>

                                    @if($step->tip)
                                        <div class="mt-4 bg-amber-900/20 border border-amber-800 rounded-lg p-3">
                                            <p class="text-amber-400 text-sm">💡 <strong>Tipp:</strong> {{ $step->tip }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Attachments --}}
        @if($tutorial->attachments && count($tutorial->attachments) > 0)
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
                <h3 class="text-lg font-semibold text-white mb-4">📎 Downloads & Anhänge</h3>
                <div class="space-y-2">
                    @foreach($tutorial->attachments as $attachment)
                        <a href="{{ Storage::disk('s3')->url($attachment) }}" target="_blank"
                           class="flex items-center space-x-3 text-gray-300 hover:text-amber-400 bg-gray-700 rounded-lg px-4 py-3 text-sm transition-colors">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>{{ basename($attachment) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Voting --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
            <h3 class="text-lg font-semibold text-white mb-3">War dieses Tutorial hilfreich?</h3>
            <div class="flex items-center gap-4">
                @auth
                    <form action="{{ route('tutorials.vote', $tutorial) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="is_helpful" value="1">
                        <button class="px-6 py-2 rounded-lg text-sm font-medium transition-colors
                            {{ $userVote && $userVote->is_helpful ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-green-600 hover:text-white' }}">
                            👍 Ja ({{ $tutorial->helpful_count }})
                        </button>
                    </form>
                    <form action="{{ route('tutorials.vote', $tutorial) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="is_helpful" value="0">
                        <button class="px-6 py-2 rounded-lg text-sm font-medium transition-colors
                            {{ $userVote && !$userVote->is_helpful ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-red-600 hover:text-white' }}">
                            👎 Nein ({{ $tutorial->not_helpful_count }})
                        </button>
                    </form>
                @else
                    <p class="text-gray-500 text-sm"><a href="{{ route('login') }}" class="text-amber-400 hover:underline">Einloggen</a> um abzustimmen.</p>
                @endauth
            </div>
        </div>

        {{-- Related Tutorials --}}
        @if($related->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-white mb-4">Ähnliche Tutorials</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($related as $rel)
                        <a href="{{ route('tutorials.show', $rel->slug) }}" class="bg-gray-800 rounded-lg border border-gray-700 p-4 hover:border-amber-600 transition-colors">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs px-2 py-0.5 rounded
                                    {{ $rel->difficulty === 'beginner' ? 'bg-green-900/50 text-green-400' : '' }}
                                    {{ $rel->difficulty === 'intermediate' ? 'bg-amber-900/50 text-amber-400' : '' }}
                                    {{ $rel->difficulty === 'advanced' ? 'bg-red-900/50 text-red-400' : '' }}">
                                    {{ ucfirst($rel->difficulty) }}
                                </span>
                                @if($rel->estimated_minutes)
                                    <span class="text-gray-500 text-xs">⏱ {{ $rel->estimated_minutes }} min</span>
                                @endif
                            </div>
                            <h4 class="text-amber-400 font-medium">{{ $rel->title }}</h4>
                            <p class="text-gray-400 text-sm mt-1">{{ Str::limit(strip_tags($rel->excerpt), 80) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Comments --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Kommentare ({{ $tutorial->comments->count() }})</h3>
            @auth
                <form action="{{ route('comments.store') }}" method="POST" class="mb-6">
                    @csrf
                    <input type="hidden" name="commentable_type" value="App\Models\Tutorial">
                    <input type="hidden" name="commentable_id" value="{{ $tutorial->id }}">
                    <textarea name="content" rows="3" required
                              class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm" placeholder="Frage oder Kommentar schreiben..."></textarea>
                    <button class="mt-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm">Kommentieren</button>
                </form>
            @endauth
            <div class="space-y-4">
                @foreach($tutorial->comments as $comment)
                    <div class="flex space-x-3">
                        <div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr($comment->user?->name ?? '?', 0, 2)) }}
                        </div>
                        <div>
                            <div class="text-sm">
                                <strong class="text-gray-300">{{ $comment->user?->name }}</strong>
                                <span class="text-gray-500 text-xs ml-2">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-400 text-sm mt-1">{{ $comment->content }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
