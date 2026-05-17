<x-layouts.app :title="__('testserver.launch_page_title')">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="testserverLaunch()"
         x-init="init()">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">{{ __('testserver.breadcrumb_home') }}</a> /
            <a href="{{ route('testserver.index') }}" class="hover:text-amber-400">{{ __('testserver.breadcrumb_testserver') }}</a> /
            <span class="text-gray-300">{{ __('testserver.launch_heading') }}</span>
        </nav>

        {{-- ═════════ STATE 1: SERVER + MOD WÄHLEN ═════════ --}}
        <div x-show="state === 'choose'" x-cloak>
            <h1 class="text-3xl font-bold text-white mb-2">
                🎮 {{ __('testserver.launch_heading') }}
            </h1>

            @if($mapFile)
                <p class="text-gray-400 mb-8">
                    {{ __('testserver.launch_map_label') }}:
                    <span class="text-amber-400 font-semibold">{{ $mapFile->display_title }}</span>
                </p>
            @elseif($mapSlug)
                <p class="text-gray-400 mb-8">
                    {{ __('testserver.launch_map_label') }}:
                    <span class="text-amber-400 font-semibold">{{ $mapSlug }}</span>
                </p>
            @else
                <p class="text-gray-500 italic mb-8">{{ __('testserver.launch_no_map_selected') }}</p>
            @endif

            {{-- Step 1: Server --}}
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-white mb-3">
                    1. {{ __('testserver.launch_choose_server') }}
                </h2>
                <div class="space-y-2">
                    @foreach($servers as $server)
                        @php $available = $server->isAvailable(); @endphp
                        <label class="block cursor-pointer">
                            <input type="radio" name="server" value="{{ $server->slug }}"
                                   x-model="selectedServer"
                                   @change="onServerChange()"
                                   :disabled="!{{ $available ? 'true' : 'false' }}"
                                   class="sr-only" {{ $available ? '' : 'disabled' }}>
                            @if($available)
                                <div class="p-4 rounded-lg border-2 transition-all"
                                     :class="selectedServer === '{{ $server->slug }}'
                                        ? 'border-emerald-500 bg-emerald-900/30 ring-2 ring-emerald-500/40 shadow-lg shadow-emerald-500/10'
                                        : 'bg-gray-800/60 border-gray-700 hover:border-emerald-500/60 hover:bg-gray-800'">
                            @else
                                <div class="p-4 rounded-lg border-2 transition-all bg-gray-900/40 border-gray-800 opacity-50 cursor-not-allowed">
                            @endif
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-white">{{ $server->name }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            {{ __('testserver.card_minutes', ['min' => $server->max_session_minutes]) }}
                                            · {{ $server->max_players }} {{ __('testserver.card_max_players') }}
                                        </div>
                                    </div>
                                    @if($available)
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-400">
                                            {{ __('testserver.status_idle') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-red-500/20 text-red-400">
                                            {{ __('testserver.status_active') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Step 2: Mod --}}
            <div class="mb-6" x-show="selectedServer" x-cloak>
                <h2 class="text-lg font-semibold text-white mb-3">
                    2. {{ __('testserver.launch_choose_mod') }}
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <template x-for="mod in availableMods" :key="mod.slug">
                        <label class="block cursor-pointer">
                            <input type="radio" name="mod" :value="mod.slug" x-model="selectedMod"
                                   class="peer sr-only">
                            <div class="p-3 rounded-lg border bg-gray-800/60 border-gray-700
                                        hover:border-amber-500/60 transition-all
                                        peer-checked:border-amber-500 peer-checked:bg-amber-900/20">
                                <div class="font-semibold text-white text-sm" x-text="mod.display_name"></div>
                                <div class="text-xs text-gray-400 mt-0.5" x-text="mod.short_description"></div>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Error Message --}}
            <div x-show="errorMessage" x-cloak
                 class="mb-4 p-3 bg-red-900/30 border border-red-700/50 rounded-lg text-red-300 text-sm"
                 x-text="errorMessage"></div>

            {{-- Submit --}}
            <div class="flex gap-3 mt-8">
                <a href="{{ url()->previous() }}"
                   class="px-4 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg">
                    ← {{ __('testserver.launch_btn_back') }}
                </a>
                <button @click="reserve()"
                        :disabled="!canReserve || submitting"
                        :class="canReserve && !submitting
                            ? 'bg-emerald-600 hover:bg-emerald-500'
                            : 'bg-gray-700 cursor-not-allowed'"
                        class="flex-1 px-4 py-2.5 text-white font-semibold rounded-lg transition-colors">
                    <span x-show="!submitting">🚀 {{ __('testserver.launch_btn_reserve') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('testserver.launch_reserving') }}</span>
                </button>
            </div>
        </div>

        {{-- ═════════ STATE 2: RESERVING (Loading) ═════════ --}}
        <div x-show="state === 'reserving'" x-cloak class="text-center py-20">
            <div class="inline-block w-16 h-16 border-4 border-amber-500 border-t-transparent
                        rounded-full animate-spin mb-6"></div>
            <h2 class="text-2xl font-bold text-white mb-2">
                {{ __('testserver.launch_reserving') }}
            </h2>
            <p class="text-gray-400">{{ __('testserver.launch_reserving_hint') }}</p>
        </div>
    </div>

    {{-- Data für JS: Server-Mod-Mapping --}}
    <script>
        // Map jeder Server-Slug zu seinen erlaubten Mods
        window.__testserverData = {
            serverMods: {
                @foreach($servers as $server)
                    @php $allowed = $server->allowedMods(); @endphp
                    "{{ $server->slug }}": [
                        @foreach($allowed as $mod)
                        {
                            slug: "{{ $mod->slug }}",
                            display_name: @json($mod->display_name),
                            short_description: @json($mod->short_description ?? ''),
                            fs_game_dir: "{{ $mod->fs_game_dir }}",
                        },
                        @endforeach
                    ],
                @endforeach
            },
            mapSlug: @json($mapSlug ?? ''),
            csrfToken: "{{ csrf_token() }}",
            urls: {
                reserve: "{{ route('testserver.reserve') }}",
            },
            i18n: {
                selectServerFirst: @json(__('testserver.launch_select_server_first')),
                selectModFirst:    @json(__('testserver.launch_select_mod_first')),
                genericError:      @json(__('testserver.launch_error_generic')),
            }
        };

        function testserverLaunch() {
            return {
                state: 'choose',
                selectedServer: null,
                selectedMod: null,
                availableMods: [],
                submitting: false,
                errorMessage: '',

                init() {
                    // Vorbelegung: erster freier Server
                    const firstFreeServer = Object.keys(window.__testserverData.serverMods)[0];
                    if (firstFreeServer) {
                        this.selectedServer = firstFreeServer;
                        this.onServerChange();
                    }
                },

                onServerChange() {
                    this.availableMods = window.__testserverData.serverMods[this.selectedServer] || [];
                    // Wenn der gewählte Mod nicht in der neuen Mod-Liste ist, reset
                    if (this.selectedMod && !this.availableMods.find(m => m.slug === this.selectedMod)) {
                        this.selectedMod = null;
                    }
                    // Default: ersten Mod auswählen
                    if (!this.selectedMod && this.availableMods.length) {
                        this.selectedMod = this.availableMods[0].slug;
                    }
                },

                get canReserve() {
                    return this.selectedServer && this.selectedMod;
                },

                async reserve() {
                    const data = window.__testserverData;

                    if (!this.selectedServer) {
                        this.errorMessage = data.i18n.selectServerFirst;
                        return;
                    }
                    if (!this.selectedMod) {
                        this.errorMessage = data.i18n.selectModFirst;
                        return;
                    }

                    this.submitting = true;
                    this.errorMessage = '';
                    this.state = 'reserving';

                    try {
                        const res = await fetch(data.urls.reserve, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': data.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                testserver_slug: this.selectedServer,
                                mod_slug: this.selectedMod,
                                map_slug: data.mapSlug || 'oasis',
                            }),
                        });

                        const json = await res.json();

                        if (json.success && json.redirect) {
                            // Direkt zur Connect-Page
                            window.location.href = json.redirect;
                        } else {
                            // Fehler anzeigen, zurück zu choose
                            this.state = 'choose';
                            this.errorMessage = json.error || data.i18n.genericError;
                            this.submitting = false;
                        }
                    } catch (err) {
                        this.state = 'choose';
                        this.errorMessage = data.i18n.genericError + ' (' + err.message + ')';
                        this.submitting = false;
                    }
                },
            };
        }
    </script>
</x-layouts.app>
