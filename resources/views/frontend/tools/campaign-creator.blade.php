<x-layouts.app>

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .map-scroll::-webkit-scrollbar { width: 4px; }
    .map-scroll::-webkit-scrollbar-track { background: transparent; }
    .map-scroll::-webkit-scrollbar-thumb { background: #374151; border-radius: 2px; }
    .preview-kw { color: #d97706; }
    .preview-str { color: #10b981; }
    .preview-cmt { color: #6b7280; font-style: italic; }
    .campaign-card { transition: all 0.2s ease; }
    .campaign-card:hover { border-color: rgba(217, 119, 6, 0.4); }
    .dl-card { transition: all 0.2s ease; }
    .dl-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
    .autocomplete-dropdown {
        position: absolute; z-index: 50; top: 100%; left: 0; right: 0;
        max-height: 200px; overflow-y: auto;
        background: #111827; border: 1px solid #374151; border-radius: 0.5rem; margin-top: 2px;
    }
    .autocomplete-item { padding: 0.4rem 0.75rem; font-size: 0.8rem; cursor: pointer; transition: background 0.15s; }
    .autocomplete-item:hover { background: rgba(217, 119, 6, 0.15); }
</style>
@endpush

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="campaignCreator()" x-cloak>

    {{-- ═══ HEADER ═══ --}}
    <div class="text-center mb-8">
        <p class="text-xs font-mono tracking-[0.25em] uppercase text-amber-500 mb-1">{{ __('messages.cc_tools_label') }}</p>
        <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-200 via-amber-500 to-amber-400 bg-clip-text text-transparent">
            {{ __('messages.cc_title') }}
        </h1>
        <p class="text-gray-400 text-sm mt-2">{{ __('messages.cc_subtitle') }}</p>
        <div class="w-24 h-0.5 bg-gradient-to-r from-transparent via-amber-600 to-transparent mx-auto mt-4"></div>
    </div>

    {{-- ═══ STEPS BAR ═══ --}}
    <div class="flex justify-center items-center gap-0 mb-8">
        @php $stepLabels = [__('messages.cc_step_settings'), __('messages.cc_step_maps'), __('messages.cc_step_preview')]; @endphp
        @foreach($stepLabels as $i => $label)
            <div class="flex items-center">
                <button @click="goToStep({{ $i + 1 }})" class="flex items-center gap-2 px-3 py-1.5 cursor-pointer">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-mono border-2 transition-all"
                          :class="{
                              'border-amber-500 bg-amber-500 text-black': step === {{ $i + 1 }},
                              'border-emerald-500 bg-emerald-500 text-black': step > {{ $i + 1 }},
                              'border-gray-600 text-gray-500': step < {{ $i + 1 }}
                          }">
                        <span x-show="step <= {{ $i + 1 }}">{{ $i + 1 }}</span>
                        <span x-show="step > {{ $i + 1 }}">✓</span>
                    </span>
                    <span class="text-xs font-medium hidden sm:inline"
                          :class="step === {{ $i + 1 }} ? 'text-gray-200' : 'text-gray-500'">{{ $label }}</span>
                </button>
                @if($i < 2)<div class="w-6 h-px bg-gray-700"></div>@endif
            </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- STEP 1: SETTINGS                           --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="step === 1" x-transition.opacity.duration.200ms>
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>

            <h2 class="text-lg font-semibold text-gray-100 flex items-center gap-2 mb-6">
                <span class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-sm">⚙</span>
                {{ __('messages.cc_configuration') }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.cc_camp_name') }}</label>
                    <input type="text" x-model="config.campName"
                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition"
                           placeholder="{{ __('messages.cc_camp_name_placeholder') }}">
                    <p class="text-xs text-gray-500 mt-1 italic">{{ __('messages.cc_camp_name_hint') }}</p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.cc_short_name') }}</label>
                    <input type="text" x-model="config.campShortName"
                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition"
                           placeholder="{{ __('messages.cc_short_name_placeholder') }}">
                    <p class="text-xs text-gray-500 mt-1 italic">{{ __('messages.cc_short_name_hint') }}</p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.cc_pk3_filename') }}</label>
                    <input type="text" x-model="config.pk3Name"
                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition"
                           placeholder="{{ __('messages.cc_pk3_filename_placeholder') }}">
                    <p class="text-xs text-gray-500 mt-1 italic">{{ __('messages.cc_pk3_filename_hint') }}</p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.cc_camp_filename') }}</label>
                    <input type="text" x-model="config.campFilename"
                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition"
                           placeholder="{{ __('messages.cc_camp_filename_placeholder') }}">
                    <p class="text-xs text-gray-500 mt-1 italic">{{ __('messages.cc_camp_filename_hint') }}</p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.cc_maps_per_campaign') }}</label>
                    <select x-model.number="config.mapsPerCampaign"
                            class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition appearance-none">
                        <template x-for="n in [4,5,6,7,8,9,10]" :key="n">
                            <option :value="n" x-text="n + ' Maps' + (n === 10 ? ' {{ __('messages.cc_maps_maximum') }}' : '')"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.cc_num_campaigns') }}</label>
                    <select x-model.number="config.numCampaigns"
                            class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition appearance-none">
                        <template x-for="n in [1,2,3,4,5,6]" :key="n">
                            <option :value="n" x-text="n + ' ' + (n === 1 ? '{{ __('messages.cc_campaign_singular') }}' : '{{ __('messages.cc_campaign_plural') }}')"></option>
                        </template>
                    </select>
                </div>

                {{-- Game Filter --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.cc_game_filter') }}</label>
                    <div class="flex flex-wrap gap-2">
                        <button @click="config.gameFilter = 'all'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                :class="config.gameFilter === 'all' ? 'border-amber-500 bg-amber-500/10 text-amber-400' : 'border-gray-700 text-gray-400 hover:border-gray-600'">
                            {{ __('messages.cc_all_games') }}
                        </button>
                        @foreach($games as $game)
                        <button @click="config.gameFilter = '{{ $game }}'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                :class="config.gameFilter === '{{ $game }}' ? 'border-amber-500 bg-amber-500/10 text-amber-400' : 'border-gray-700 text-gray-400 hover:border-gray-600'">
                            {{ $game }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6 pt-4 border-t border-gray-700">
                <button @click="goToStep(2)"
                        class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-black font-semibold text-sm rounded-lg transition shadow-lg shadow-amber-600/10">
                    {{ __('messages.cc_continue_maps') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- STEP 2: MAP SELECTION                      --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="step === 2" x-transition.opacity.duration.200ms>

        <div class="flex items-center gap-3 px-4 py-3 bg-blue-900/10 border border-blue-500/20 rounded-lg mb-5 text-sm text-gray-300">
            <span class="text-2xl font-bold text-blue-400 font-mono" x-text="filteredMaps.length"></span>
            <span>{!! __('messages.cc_maps_available') !!}</span>
        </div>

        <div class="space-y-4">
            <template x-for="(camp, ci) in campaigns" :key="ci">
                <div class="campaign-card bg-gray-800 border border-gray-700 rounded-xl overflow-hidden">
                    <div @click="camp.open = !camp.open"
                         class="flex items-center justify-between px-5 py-3 bg-amber-500/[0.03] border-b border-gray-700 cursor-pointer hover:bg-amber-500/[0.06] transition select-none">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 text-xs font-bold font-mono text-amber-400 bg-amber-500/10 border border-amber-500/25 rounded"
                                  x-text="'C' + (ci + 1)"></span>
                            <span class="font-semibold text-gray-200" x-text="config.campName + ' ' + (ci + 1)"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-gray-500"
                                  x-text="camp.maps.filter(m => m.mapname).length + '/' + config.mapsPerCampaign + ' maps'"></span>
                            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="camp.open && 'rotate-180'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    <div x-show="camp.open" x-transition>
                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <template x-for="(slot, mi) in camp.maps" :key="mi">
                                    <div>
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="w-5 h-5 rounded flex items-center justify-center text-[10px] font-mono bg-blue-500/10 border border-blue-500/20 text-blue-400"
                                                  x-text="mi + 1"></span>
                                            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-500"
                                                  x-text="'MAP ' + (mi + 1) + (mi === config.mapsPerCampaign - 1 ? ' {{ __('messages.cc_map_last') }}' : '')"></span>
                                        </div>

                                        <div class="relative">
                                            <div class="flex gap-1">
                                                {{-- Dropdown mode --}}
                                                <select x-show="!slot.manual"
                                                        x-model="slot.value"
                                                        @change="onSlotSelect(ci, mi, $event.target.value)"
                                                        class="flex-1 px-2 py-1.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-300 text-xs focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition appearance-none">
                                                    <option value="">{{ __('messages.cc_select_map') }}</option>
                                                    <optgroup label="{{ __('messages.cc_stock_maps') }}">
                                                        <template x-for="m in stockMaps" :key="'s'+m.mapname">
                                                            <option :value="m.mapname + ':' + m.pk3" x-text="m.mapname + ' {{ __('messages.cc_stock_label') }}'"></option>
                                                        </template>
                                                    </optgroup>
                                                    <optgroup label="{{ __('messages.cc_custom_maps') }}">
                                                        <template x-for="m in customMaps" :key="'c'+m.mapname">
                                                            <option :value="m.mapname + ':' + m.pk3" x-text="m.mapname + '  ⟵  ' + m.pk3"></option>
                                                        </template>
                                                    </optgroup>
                                                </select>

                                                {{-- Manual mode --}}
                                                <div x-show="slot.manual" class="flex-1 relative">
                                                    <input type="text"
                                                           x-model="slot.search"
                                                           @input.debounce.250ms="searchMap(ci, mi)"
                                                           @keydown.enter.prevent="confirmManual(ci, mi)"
                                                           @keydown.escape="slot.manual = false; slot.results = []"
                                                           @click.away="slot.results = []"
                                                           class="w-full px-2 py-1.5 bg-gray-900 border border-amber-600 rounded-lg text-amber-300 text-xs focus:ring-1 focus:ring-amber-500/30 outline-none font-mono"
                                                           placeholder="{{ __('messages.cc_type_mapname') }}">

                                                    <div x-show="slot.results && slot.results.length > 0" class="autocomplete-dropdown map-scroll">
                                                        <template x-for="(r, ri) in slot.results" :key="ri">
                                                            <div @click="pickResult(ci, mi, r)" class="autocomplete-item text-gray-300 font-mono">
                                                                <span x-text="r.mapname" class="text-amber-400"></span>
                                                                <span class="text-gray-600 text-[10px] ml-1" x-text="r.pk3"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                {{-- Toggle --}}
                                                <button @click="toggleManual(ci, mi)"
                                                        class="px-2 py-1.5 border border-gray-700 rounded-lg text-gray-500 hover:text-amber-400 hover:border-amber-600 transition text-xs flex-shrink-0"
                                                        :title="slot.manual ? '{{ __('messages.cc_switch_dropdown') }}' : '{{ __('messages.cc_switch_manual') }}'">
                                                    <span x-text="slot.manual ? '☰' : '✎'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex gap-2 mt-4 pt-3 border-t border-gray-700">
                                <button @click="randomFill(ci)"
                                        class="px-3 py-1.5 text-xs font-medium border border-gray-700 rounded-lg text-gray-400 hover:text-gray-200 hover:border-gray-600 transition">
                                    {{ __('messages.cc_random_fill') }}
                                </button>
                                <button @click="clearCampaign(ci)"
                                        class="px-3 py-1.5 text-xs font-medium border border-red-900/30 rounded-lg text-red-500 hover:bg-red-900/10 transition">
                                    {{ __('messages.cc_clear') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex justify-between mt-6">
            <button @click="goToStep(1)" class="px-4 py-2 border border-gray-700 text-gray-400 hover:text-gray-200 text-sm rounded-lg transition">
                {{ __('messages.cc_back_settings') }}
            </button>
            <button @click="generateAndPreview()" class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-black font-semibold text-sm rounded-lg transition shadow-lg shadow-amber-600/10">
                {{ __('messages.cc_generate_preview') }}
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- STEP 3: PREVIEW & EXPORT                   --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div x-show="step === 3" x-transition.opacity.duration.200ms>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-amber-400" x-text="config.numCampaigns"></div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-1">{{ __('messages.cc_campaigns') }}</div>
            </div>
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-amber-400" x-text="stats.totalMaps"></div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-1">{{ __('messages.cc_total_maps') }}</div>
            </div>
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-amber-400" x-text="stats.customPk3s"></div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-1">{{ __('messages.cc_custom_pk3s') }}</div>
            </div>
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-amber-400">3</div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-1">{{ __('messages.cc_output_files') }}</div>
            </div>
        </div>

        {{-- Required PK3s --}}
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 mb-4 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
            <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">📋</span>
                {{ __('messages.cc_required_pk3s') }}
            </h3>
            <p class="text-xs text-gray-400 mb-3">{{ __('messages.cc_required_pk3s_desc') }}</p>
            <div class="flex flex-wrap gap-1.5" x-show="stats.requiredPk3s.length > 0">
                <template x-for="pk3 in stats.requiredPk3s" :key="pk3">
                    <span class="px-2 py-0.5 text-[11px] font-mono bg-amber-500/10 border border-amber-500/20 rounded text-amber-400" x-text="pk3"></span>
                </template>
            </div>
            <p x-show="stats.requiredPk3s.length === 0" class="text-xs text-gray-500 italic">{{ __('messages.cc_no_custom_pk3s') }}</p>
        </div>

        {{-- Preview --}}
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 mb-4 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
            <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">👁</span>
                {{ __('messages.cc_file_preview') }}
            </h3>
            <div class="flex gap-0 border-b border-gray-700">
                <button @click="previewTab = 'campaign'"
                        class="px-3 py-1.5 text-xs font-mono border border-b-0 rounded-t-lg transition"
                        :class="previewTab === 'campaign' ? 'text-amber-400 bg-gray-900 border-gray-700' : 'text-gray-500 border-transparent'">.campaign</button>
                <button @click="previewTab = 'cfg'"
                        class="px-3 py-1.5 text-xs font-mono border border-b-0 rounded-t-lg transition"
                        :class="previewTab === 'cfg' ? 'text-amber-400 bg-gray-900 border-gray-700' : 'text-gray-500 border-transparent'">campaigncycle.cfg</button>
            </div>
            <pre class="bg-gray-900 border border-gray-700 border-t-0 rounded-b-lg p-4 text-xs font-mono leading-relaxed text-gray-400 max-h-80 overflow-y-auto map-scroll"
                 x-html="previewTab === 'campaign' ? highlightedCampaign : highlightedCfg"></pre>
        </div>

        {{-- Downloads --}}
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
            <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-2">
                <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">⬇</span>
                {{ __('messages.cc_download_files') }}
            </h3>
            <p class="text-xs text-gray-400 mb-4">{!! __('messages.cc_download_hint') !!}</p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button @click="downloadPK3()" class="dl-card bg-gray-900 border border-gray-700 rounded-xl p-4 text-center hover:border-amber-500 cursor-pointer">
                    <div class="text-2xl mb-1">📦</div>
                    <div class="font-semibold text-sm text-gray-200" x-text="config.pk3Name || 'campaign.pk3'"></div>
                    <div class="text-[10px] text-gray-500 mt-0.5">{{ __('messages.cc_pk3_mod_folder') }}</div>
                </button>
                <button @click="downloadFile(generated.cfg, 'campaigncycle.cfg')" class="dl-card bg-gray-900 border border-gray-700 rounded-xl p-4 text-center hover:border-amber-500 cursor-pointer">
                    <div class="text-2xl mb-1">📄</div>
                    <div class="font-semibold text-sm text-gray-200">campaigncycle.cfg</div>
                    <div class="text-[10px] text-gray-500 mt-0.5">{{ __('messages.cc_cfg_etmain') }}</div>
                </button>
                <button @click="downloadFile(generated.filelist, 'filelist.txt')" class="dl-card bg-gray-900 border border-gray-700 rounded-xl p-4 text-center hover:border-amber-500 cursor-pointer">
                    <div class="text-2xl mb-1">📝</div>
                    <div class="font-semibold text-sm text-gray-200">filelist.txt</div>
                    <div class="text-[10px] text-gray-500 mt-0.5">{{ __('messages.cc_filelist_label') }}</div>
                </button>
                <button @click="downloadAll()" class="dl-card bg-gray-900 border border-amber-600/30 rounded-xl p-4 text-center hover:border-amber-500 cursor-pointer">
                    <div class="text-2xl mb-1">🎁</div>
                    <div class="font-semibold text-sm text-amber-400">{{ __('messages.cc_download_all') }}</div>
                    <div class="text-[10px] text-gray-500 mt-0.5">{{ __('messages.cc_download_all_desc') }}</div>
                </button>
            </div>
        </div>

        <div class="flex justify-between mt-6">
            <button @click="goToStep(2)" class="px-4 py-2 border border-gray-700 text-gray-400 hover:text-gray-200 text-sm rounded-lg transition">
                {{ __('messages.cc_back_maps') }}
            </button>
            <button @click="goToStep(1)" class="px-4 py-2 border border-gray-700 text-gray-400 hover:text-gray-200 text-sm rounded-lg transition">
                {{ __('messages.cc_start_over') }}
            </button>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition
         class="fixed bottom-6 right-6 px-4 py-2.5 bg-gray-800 border border-emerald-500 rounded-lg text-emerald-400 text-sm font-medium shadow-xl z-50">
        <span x-text="'✓ ' + toast.msg"></span>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
const ALL_MAPS = @json($maps);
const SEARCH_URL = @json(route('tools.campaign-creator.search-maps'));

function campaignCreator() {
    return {
        step: 1,
        previewTab: 'campaign',
        toast: { show: false, msg: '' },

        config: {
            campName: 'Wolffiles Campaign',
            campShortName: 'WF-Camp',
            pk3Name: 'wf_campaign_v1.pk3',
            campFilename: 'wf_rotation',
            mapsPerCampaign: 6,
            numCampaigns: 4,
            gameFilter: 'all',
        },

        campaigns: [],
        generated: { campaign: '', cfg: '', filelist: '' },
        stats: { totalMaps: 0, customPk3s: 0, requiredPk3s: [] },
        highlightedCampaign: '',
        highlightedCfg: '',

        get filteredMaps() {
            if (this.config.gameFilter === 'all') return ALL_MAPS;
            return ALL_MAPS.filter(m => m.game === this.config.gameFilter);
        },
        get stockMaps() { return this.filteredMaps.filter(m => m.is_stock); },
        get customMaps() { return this.filteredMaps.filter(m => !m.is_stock); },

        init() {
            this.$watch('config.numCampaigns', () => this.buildCampaigns());
            this.$watch('config.mapsPerCampaign', () => this.buildCampaigns());
            this.buildCampaigns();
        },

        buildCampaigns() {
            const old = {};
            this.campaigns.forEach((c, ci) => c.maps.forEach((m, mi) => {
                if (m.mapname) old[`${ci}-${mi}`] = { ...m };
            }));
            this.campaigns = [];
            for (let c = 0; c < this.config.numCampaigns; c++) {
                const maps = [];
                for (let m = 0; m < this.config.mapsPerCampaign; m++) {
                    const prev = old[`${c}-${m}`];
                    maps.push(prev ? { ...prev, manual: false, search: '', results: [] }
                                   : { mapname: '', pk3: '', value: '', manual: false, search: '', results: [] });
                }
                this.campaigns.push({ open: c === 0, maps });
            }
        },

        goToStep(s) {
            if (s === 3) this.generateAndPreview();
            this.step = s;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // Map slot interactions
        onSlotSelect(ci, mi, val) {
            const slot = this.campaigns[ci].maps[mi];
            if (val) {
                const [mapname, pk3] = val.split(':');
                slot.mapname = mapname;
                slot.pk3 = pk3;
                slot.value = val;
            } else {
                slot.mapname = ''; slot.pk3 = ''; slot.value = '';
            }
        },

        toggleManual(ci, mi) {
            const slot = this.campaigns[ci].maps[mi];
            slot.manual = !slot.manual;
            if (slot.manual) {
                slot.search = slot.mapname || '';
                slot.results = [];
            }
        },

        async searchMap(ci, mi) {
            const slot = this.campaigns[ci].maps[mi];
            const q = (slot.search || '').trim();
            if (q.length < 2) { slot.results = []; return; }

            // Local search first
            const local = ALL_MAPS.filter(m =>
                m.mapname.toLowerCase().includes(q.toLowerCase()) ||
                m.pk3.toLowerCase().includes(q.toLowerCase())
            ).slice(0, 15);
            slot.results = local;

            // API search if few results
            if (local.length < 5) {
                try {
                    const resp = await fetch(`${SEARCH_URL}?q=${encodeURIComponent(q)}`);
                    const data = await resp.json();
                    const existing = new Set(local.map(m => m.mapname));
                    slot.results = [...local, ...data.filter(m => !existing.has(m.mapname))].slice(0, 20);
                } catch(e) {}
            }
        },

        pickResult(ci, mi, r) {
            const slot = this.campaigns[ci].maps[mi];
            slot.mapname = r.mapname;
            slot.pk3 = r.pk3;
            slot.value = r.mapname + ':' + r.pk3;
            slot.search = r.mapname;
            slot.results = [];
        },

        confirmManual(ci, mi) {
            const slot = this.campaigns[ci].maps[mi];
            const q = (slot.search || '').trim();
            if (!q) return;
            const match = (slot.results || []).find(r => r.mapname.toLowerCase() === q.toLowerCase());
            if (match) {
                this.pickResult(ci, mi, match);
            } else {
                slot.mapname = q;
                slot.pk3 = q + '.pk3';
                slot.value = q + ':' + q + '.pk3';
                slot.results = [];
            }
        },

        randomFill(ci) {
            const used = new Set();
            this.campaigns.forEach((c, i) => {
                if (i !== ci) c.maps.forEach(m => { if (m.mapname) used.add(m.mapname); });
            });
            const available = this.filteredMaps.filter(m => !used.has(m.mapname));
            const shuffled = [...available].sort(() => Math.random() - 0.5);
            this.campaigns[ci].maps.forEach((slot, i) => {
                if (i < shuffled.length) {
                    slot.mapname = shuffled[i].mapname;
                    slot.pk3 = shuffled[i].pk3;
                    slot.value = shuffled[i].mapname + ':' + shuffled[i].pk3;
                }
            });
        },

        clearCampaign(ci) {
            this.campaigns[ci].maps.forEach(s => {
                s.mapname = ''; s.pk3 = ''; s.value = ''; s.search = ''; s.results = [];
            });
        },

        generateAndPreview() {
            const { campName, campShortName, campFilename } = this.config;
            const requiredPK3s = new Set();
            let totalMaps = 0, campaign = '', cfg = '';

            this.campaigns.forEach((camp, ci) => {
                const maps = camp.maps.filter(m => m.mapname);
                if (!maps.length) return;
                totalMaps += maps.length;
                const names = maps.map(m => m.mapname);
                const desc = names.map((m, i) => `${i % 2 === 0 ? '^2' : '^4'}${i+1}. ${m}`).join('* ');
                maps.forEach(m => { if (m.pk3 && m.pk3 !== 'pak0.pk3') requiredPK3s.add(m.pk3); });

                campaign += `{\n        name                   "${campName} ${ci+1}"\n`;
                campaign += `        shortname              "${campShortName}${ci+1}"\n`;
                campaign += `        description            "^7${campName} ${ci+1}*^0Created with Wolffiles.eu**^4Maps:* ${desc}* "\n`;
                campaign += `        maps                   "${names.join(';')}"\n`;
                campaign += `        mapTC                  374 374\n        type                   "wolfmp"\n}\n\n`;
            });

            cfg = `//***************************//\n// Campaign Rotation Script  //\n// Generated by Wolffiles.eu //\n//***************************//\n\n`;
            cfg += `set b_campaignfile "scripts/${campFilename}.campaign"\n\n`;
            this.campaigns.forEach((camp, ci) => {
                const maps = camp.maps.filter(m => m.mapname);
                if (!maps.length) return;
                const next = ci + 1 < this.campaigns.length ? `cmp${ci + 2}` : 'cmp1';
                cfg += `set cmp${ci+1} "set b_campaignfile \\"scripts/${campFilename}.campaign\\" ; set g_gametype 4 ; map ${maps[0].mapname} ; set nextcampaign vstr cmp${ci+1} ; campaign ${campShortName}${ci+1} ; set nextcampaign vstr ${next}"\n\n`;
            });
            cfg += `vstr cmp1\n`;

            let filelist = `// Required PK3 files\n// Generated by Wolffiles.eu\n// ${new Date().toISOString().split('T')[0]}\n\n`;
            [...requiredPK3s].sort().forEach(pk3 => { filelist += pk3 + '\n'; });

            this.generated = { campaign, cfg, filelist };
            this.stats = { totalMaps, customPk3s: requiredPK3s.size, requiredPk3s: [...requiredPK3s].sort() };
            this.highlightedCampaign = this.hl(campaign);
            this.highlightedCfg = this.hl(cfg);
            this.step = 3;
        },

        hl(t) {
            return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/(\/\/.*$)/gm, '<span class="preview-cmt">$1</span>')
                .replace(/"([^"]*)"/g, '"<span class="preview-str">$1</span>"')
                .replace(/\b(name|shortname|description|maps|mapTC|type|set|map|campaign|vstr)\b/g, '<span class="preview-kw">$1</span>');
        },

        showToast(m) { this.toast.msg = m; this.toast.show = true; setTimeout(() => this.toast.show = false, 2500); },

        downloadFile(content, filename) {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([content], { type: 'text/plain' }));
            a.download = filename; a.click(); this.showToast(filename);
        },

        async downloadPK3() {
            const zip = new JSZip();
            zip.folder('scripts').file(this.config.campFilename + '.campaign', this.generated.campaign);
            const a = document.createElement('a');
            a.href = URL.createObjectURL(await zip.generateAsync({ type: 'blob' }));
            a.download = this.config.pk3Name || 'campaign.pk3'; a.click();
            this.showToast(this.config.pk3Name);
        },

        async downloadAll() {
            const zip = new JSZip();
            const pk3 = new JSZip();
            pk3.folder('scripts').file(this.config.campFilename + '.campaign', this.generated.campaign);
            zip.file(this.config.pk3Name || 'campaign.pk3', await pk3.generateAsync({ type: 'blob' }));
            zip.file('campaigncycle.cfg', this.generated.cfg);
            zip.file('filelist.txt', this.generated.filelist);
            const a = document.createElement('a');
            a.href = URL.createObjectURL(await zip.generateAsync({ type: 'blob' }));
            a.download = 'campaign_bundle.zip'; a.click();
            this.showToast('campaign_bundle.zip');
        },
    };
}
</script>

</x-layouts.app>
