<x-layouts.app :title="__('testserver.session_page_title')">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="testserverSession()"
         x-init="init()">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">{{ __('testserver.breadcrumb_home') }}</a> /
            <a href="{{ route('testserver.index') }}" class="hover:text-amber-400">{{ __('testserver.breadcrumb_testserver') }}</a> /
            <span class="text-gray-300">{{ __('testserver.breadcrumb_session') }}</span>
        </nav>

        {{-- ═════════ STATE: LOADING (Server startet) ═════════ --}}
        <div x-show="['pending','launching'].includes(status)" x-cloak class="text-center py-16">
            <div class="inline-block w-20 h-20 border-4 border-amber-500 border-t-transparent
                        rounded-full animate-spin mb-6"></div>
            <h2 class="text-2xl font-bold text-white mb-2">
                {{ __('testserver.session_loading') }}
            </h2>
            <p class="text-gray-400 max-w-md mx-auto">
                {{ __('testserver.launch_reserving_hint') }}
            </p>

            <div class="mt-8 inline-flex flex-col items-center gap-2 text-sm text-gray-500">
                <div>{{ __('testserver.session_map') }}: <span class="text-gray-300 font-mono">{{ $session->map_slug }}</span></div>
                <div>{{ __('testserver.session_mod') }}: <span class="text-gray-300 font-mono">{{ $session->mod_name }}</span></div>
            </div>
        </div>

        {{-- ═════════ STATE: ACTIVE (Server bereit, Connect-Info) ═════════ --}}
        <div x-show="status === 'active'" x-cloak>

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <span class="text-2xl">🎮</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ __('testserver.session_ready') }}</h1>
                    <p class="text-sm text-gray-400">{{ $session->testserver->name }}</p>
                </div>
            </div>

            {{-- Countdown Card --}}
            <div class="bg-gradient-to-br from-emerald-900/30 to-emerald-900/10 border border-emerald-700/40
                        rounded-lg p-6 mb-6">
                <div class="text-center">
                    <div class="text-sm text-emerald-400 uppercase tracking-wider mb-2">
                        {{ __('testserver.session_remaining') }}
                    </div>
                    <div class="text-5xl font-bold text-white font-mono tabular-nums" x-text="formatRemaining(remaining)">
                        --:--
                    </div>
                </div>
            </div>

            {{-- Connect Info Card --}}
            <div class="bg-gray-800/60 border border-gray-700 rounded-lg p-6 mb-6 space-y-4">

                {{-- Server Address --}}
                <div>
                    <label class="text-xs uppercase tracking-wider text-gray-500 mb-1.5 block">
                        {{ __('testserver.session_connect_address') }}
                    </label>
                    <div class="flex gap-2">
                        <input readonly :value="connectAddress"
                               class="flex-1 bg-gray-900 border border-gray-700 text-white font-mono
                                      px-3 py-2 rounded-lg text-sm"
                               @click="$el.select()">
                        <button @click="copy(connectAddress, 'address')"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition-colors">
                            <span x-show="copiedField !== 'address'">📋</span>
                            <span x-show="copiedField === 'address'" x-cloak>✓</span>
                        </button>
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label class="text-xs uppercase tracking-wider text-gray-500 mb-1.5 block">
                        {{ __('testserver.session_connect_password') }}
                    </label>
                    <div class="flex gap-2">
                        <input readonly :value="connectPassword"
                               class="flex-1 bg-gray-900 border border-gray-700 text-white font-mono
                                      px-3 py-2 rounded-lg text-sm"
                               @click="$el.select()">
                        <button @click="copy(connectPassword, 'password')"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition-colors">
                            <span x-show="copiedField !== 'password'">📋</span>
                            <span x-show="copiedField === 'password'" x-cloak>✓</span>
                        </button>
                    </div>
                </div>

                {{-- Map + Mod info --}}
                <div class="grid grid-cols-2 gap-4 pt-3 border-t border-gray-700">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">{{ __('testserver.session_map') }}</div>
                        <div class="text-white font-mono mt-1" x-text="mapSlug"></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">{{ __('testserver.session_mod') }}</div>
                        <div class="text-white font-mono mt-1" x-text="modName"></div>
                    </div>
                </div>
            </div>

            {{-- ET-Protocol Connect-Button --}}
            <a :href="`et://${connectAddress}/${encodeURIComponent(connectPassword)}`"
               class="block w-full text-center px-4 py-4 bg-emerald-600 hover:bg-emerald-500
                      text-white font-bold rounded-lg text-lg transition-colors mb-3">
                🎯 {{ __('testserver.session_btn_connect') }}
            </a>

            {{-- Cancel-Button --}}
            <button @click="cancelSession()"
                    class="block w-full text-center px-4 py-2.5 bg-gray-800 hover:bg-gray-700
                           text-gray-300 rounded-lg text-sm transition-colors">
                🛑 {{ __('testserver.session_btn_cancel') }}
            </button>

            {{-- Help --}}
            <p class="mt-6 text-xs text-gray-500 text-center">
                {{ __('testserver.session_connect_help', ['address' => $session->connect_address, 'password' => '<password>']) }}
            </p>
        </div>

        {{-- ═════════ STATE: ENDED (expired/cancelled) ═════════ --}}
        <div x-show="['expired','cancelled'].includes(status)" x-cloak class="text-center py-16">
            <div class="w-20 h-20 mx-auto rounded-full bg-gray-700/60 flex items-center justify-center mb-6">
                <span class="text-3xl">✓</span>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">
                {{ __('testserver.session_ended') }}
            </h2>
            <p class="text-gray-400 mb-8" x-text="status === 'cancelled'
                ? '{{ __('testserver.status_cancelled') }}'
                : '{{ __('testserver.status_expired') }}'"></p>

            <a href="{{ route('testserver.index') }}"
               class="inline-block px-6 py-3 bg-emerald-600 hover:bg-emerald-500
                      text-white font-semibold rounded-lg transition-colors">
                {{ __('testserver.session_btn_new_session') }}
            </a>
        </div>

        {{-- ═════════ STATE: FAILED ═════════ --}}
        <div x-show="status === 'failed'" x-cloak class="text-center py-16">
            <div class="w-20 h-20 mx-auto rounded-full bg-red-700/30 flex items-center justify-center mb-6">
                <span class="text-3xl">⚠️</span>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">
                {{ __('testserver.session_failed') }}
            </h2>
            <p class="text-gray-400 mb-2 max-w-md mx-auto" x-text="errorMessage || ''"></p>
            <a href="{{ route('testserver.index') }}"
               class="inline-block mt-6 px-6 py-3 bg-gray-700 hover:bg-gray-600
                      text-white rounded-lg transition-colors">
                ← {{ __('testserver.launch_btn_back') }}
            </a>
        </div>
    </div>

    {{-- Alpine Component --}}
    <script>
        window.__sessionData = {
            token:           @json($session->session_token),
            status:          @json($session->status),
            mapSlug:         @json($session->map_slug),
            modName:         @json($session->mod_name),
            connectAddress:  @json($session->connect_address),
            connectPassword: @json($session->connect_password),
            errorMessage:    @json($session->error_message ?? ''),
            urls: {
                status: @json(route('testserver.status', $session->session_token)),
                cancel: @json(route('testserver.cancel', $session->session_token)),
            },
            csrfToken: "{{ csrf_token() }}",
            i18n: {
                cancelConfirm: @json(__('testserver.session_cancel_confirm')),
                cancelSuccess: @json(__('testserver.session_cancel_success')),
                copied:        @json(__('testserver.session_copied')),
                minutesShort:  @json(__('testserver.session_minutes_short')),
                secondsShort:  @json(__('testserver.session_seconds_short')),
            }
        };

        function testserverSession() {
            return {
                token:           window.__sessionData.token,
                status:          window.__sessionData.status,
                mapSlug:         window.__sessionData.mapSlug,
                modName:         window.__sessionData.modName,
                connectAddress:  window.__sessionData.connectAddress,
                connectPassword: window.__sessionData.connectPassword,
                errorMessage:    window.__sessionData.errorMessage,
                remaining:       0,
                copiedField:     null,
                pollIntervalId:  null,
                countdownId:     null,

                init() {
                    this.poll();
                    // Status alle 3 Sek pollen
                    this.pollIntervalId = setInterval(() => this.poll(), 3000);
                    // Countdown jede Sekunde lokal runterzählen
                    this.countdownId = setInterval(() => {
                        if (this.status === 'active' && this.remaining > 0) {
                            this.remaining--;
                        }
                    }, 1000);

                    // Stop pollen wenn Tab geschlossen
                    window.addEventListener('beforeunload', () => this.cleanup());
                },

                cleanup() {
                    if (this.pollIntervalId) clearInterval(this.pollIntervalId);
                    if (this.countdownId) clearInterval(this.countdownId);
                },

                async poll() {
                    try {
                        const res = await fetch(window.__sessionData.urls.status);
                        if (!res.ok) return;
                        const data = await res.json();

                        // Status update
                        this.status = data.status;
                        this.connectAddress = data.connect_address;
                        this.connectPassword = data.connect_password;
                        this.mapSlug = data.map_slug;
                        this.modName = data.mod_name;
                        this.remaining = data.remaining_seconds;
                        this.errorMessage = data.error_message || '';

                        // Bei Endzustand → Polling stoppen
                        if (['expired','cancelled','failed'].includes(this.status)) {
                            this.cleanup();
                        }
                    } catch (e) {
                        console.error('Status poll failed:', e);
                    }
                },

                formatRemaining(seconds) {
                    if (seconds <= 0) return '0:00';
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    return `${m}:${s.toString().padStart(2,'0')}`;
                },

                async copy(text, field) {
                    try {
                        await navigator.clipboard.writeText(text);
                        this.copiedField = field;
                        setTimeout(() => this.copiedField = null, 1500);
                    } catch (e) {
                        // Fallback für ältere Browser
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        this.copiedField = field;
                        setTimeout(() => this.copiedField = null, 1500);
                    }
                },

                async cancelSession() {
                    if (!confirm(window.__sessionData.i18n.cancelConfirm)) return;
                    try {
                        const res = await fetch(window.__sessionData.urls.cancel, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': window.__sessionData.csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                        const json = await res.json();
                        if (json.success) {
                            // Status wird beim nächsten Poll auf "cancelled" springen
                            this.poll();
                        }
                    } catch (e) {
                        console.error('Cancel failed:', e);
                    }
                },
            };
        }
    </script>
</x-layouts.app>
