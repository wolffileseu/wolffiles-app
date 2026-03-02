{{-- Partner Sidebar - Auto-Scrolling per Group --}}
@php
    $allPartners = \App\Models\PartnerLink::where('is_active', true)->orderBy('sort_order')->get();

    $groupLabels = [
        'clan' => 'Clans & Communities',
        'mod' => 'Mods',
        'other' => 'Links',
        'server' => 'Servers',
        'community' => 'Communities',
    ];

    $partnerGroups = $allPartners->groupBy('group');
@endphp

@if($allPartners->isNotEmpty())
    <div class="space-y-6">
        @foreach($partnerGroups as $groupKey => $partners)
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 py-4 px-4">
                <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wider text-center mb-4">
                    {{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}
                </h3>

                @if($partners->count() <= 4)
                    <div class="flex justify-center items-center flex-wrap gap-4">
                        @foreach($partners as $partner)
                            <a href="{{ $partner->url }}" target="_blank" rel="noopener noreferrer"
                               class="opacity-80 hover:opacity-100 transition-opacity" title="{{ $partner->name }}">
                                @if($partner->image)
                                    <img src="{{ Str::startsWith($partner->image, 'http') ? $partner->image : Storage::disk('s3')->url($partner->image) }}"
                                         alt="{{ $partner->name }}" class="h-10 max-w-[160px] object-contain">
                                @else
                                    <span class="text-gray-400 text-sm bg-gray-700 px-3 py-1.5 rounded hover:bg-gray-600">{{ $partner->name }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="overflow-hidden relative"
                         x-data="{
                            paused: false,
                            pos: 0,
                            init() {
                                const track = this.$refs.track;
                                track.innerHTML = track.innerHTML + track.innerHTML;
                                const speed = 0.4;
                                const animate = () => {
                                    if (!this.paused) {
                                        this.pos -= speed;
                                        if (Math.abs(this.pos) >= track.scrollWidth / 2) {
                                            this.pos = 0;
                                        }
                                        track.style.transform = `translateX(${this.pos}px)`;
                                    }
                                    requestAnimationFrame(animate);
                                };
                                requestAnimationFrame(animate);
                            }
                         }"
                         @mouseenter="paused = true"
                         @mouseleave="paused = false">
                        <div class="flex items-center gap-8 whitespace-nowrap" x-ref="track">
                            @foreach($partners as $partner)
                                <a href="{{ $partner->url }}" target="_blank" rel="noopener noreferrer"
                                   class="flex-shrink-0 opacity-80 hover:opacity-100 transition-opacity" title="{{ $partner->name }}">
                                    @if($partner->image)
                                        <img src="{{ Str::startsWith($partner->image, 'http') ? $partner->image : Storage::disk('s3')->url($partner->image) }}"
                                             alt="{{ $partner->name }}" class="h-10 max-w-[160px] object-contain">
                                    @else
                                        <span class="text-gray-400 text-sm bg-gray-700 px-3 py-1.5 rounded hover:bg-gray-600">{{ $partner->name }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
