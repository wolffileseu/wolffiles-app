<x-layouts.app :title="__('messages.profile_settings') ?? 'Settings'">
<style>
.snav-item{transition:all .15s;border-left:3px solid transparent;}
.snav-item.active{background:rgba(245,158,11,.1);border-left-color:#f59e0b;color:#fbbf24;}
.snav-item:not(.active):hover{background:rgba(255,255,255,.04);color:#f3f4f6;}
.ifield{width:100%;background:#111827;border:1px solid #374151;border-radius:6px;padding:9px 12px;color:#f3f4f6;font-size:13px;font-family:inherit;outline:none;transition:border-color .15s,box-shadow .15s;}
.ifield:focus{border-color:#d97706;box-shadow:0 0 0 3px rgba(245,158,11,.1);}
.ifield:disabled{opacity:.45;cursor:not-allowed;}
.scard{background:#1f2937;border:1px solid #374151;border-radius:8px;overflow:hidden;}
.scard-head{padding:14px 18px;border-bottom:1px solid #374151;display:flex;align-items:center;gap:10px;}
.scard-body{padding:18px;}
.tog{position:relative;width:40px;height:22px;flex-shrink:0;}
.tog input{opacity:0;width:0;height:0;}
.tog-tr{position:absolute;inset:0;background:#374151;border-radius:11px;cursor:pointer;transition:background .2s;}
.tog-tr::before{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;left:3px;top:3px;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.4);}
.tog input:checked+.tog-tr{background:#d97706;}
.tog input:checked+.tog-tr::before{transform:translateX(18px);}
.pw-bar{height:3px;background:#2a2e37;border-radius:2px;overflow:hidden;}
.pw-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;}
#avDropZone{border:2px dashed #374151;transition:all .2s;cursor:pointer;}
#avDropZone:hover,#avDropZone.dragover{border-color:#d97706;background:rgba(245,158,11,.05);}
</style>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-7">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('profile.show', auth()->user()) }}" class="text-amber-400 hover:text-amber-300">{{ auth()->user()->name }}</a>
            <span>›</span>
            <span>{{ __('messages.settings') }}</span>
        </div>
        <h1 class="text-2xl font-bold text-white" style="font-family:'Rajdhani',sans-serif;letter-spacing:1px;">{{ __('messages.profile_settings') }}</h1>
        <p class="text-sm text-gray-400 mt-1">{{ __('messages.settings_subtitle') }}</p>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 bg-green-900/40 border border-green-700 rounded-lg px-4 py-3 text-green-400 text-sm mb-5">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="flex items-center gap-3 bg-red-900/40 border border-red-700 rounded-lg px-4 py-3 text-red-400 text-sm mb-5">✕ {{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        {{-- SIDE NAV --}}
        <nav class="md:col-span-1">
            <div class="flex flex-col gap-0.5">
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-widest px-3 pb-2">{{ __('messages.profile') }}</div>
                <a href="#section-avatar"   class="snav-item active flex items-center gap-2.5 px-3 py-2 rounded-r-lg text-sm text-amber-400">🖼️ {{ __('messages.avatar') }}</a>
                <a href="#section-profile"  class="snav-item flex items-center gap-2.5 px-3 py-2 rounded-r-lg text-sm text-gray-400">👤 {{ __('messages.public_profile') }}</a>
                <a href="#section-notifs"   class="snav-item flex items-center gap-2.5 px-3 py-2 rounded-r-lg text-sm text-gray-400">🔔 {{ __('messages.notifications') }}</a>
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-widest px-3 py-2 mt-2">{{ __('messages.security') }}</div>
                <a href="#section-password" class="snav-item flex items-center gap-2.5 px-3 py-2 rounded-r-lg text-sm text-gray-400">🔐 {{ __('messages.password') }}</a>
                <a href="#section-discord"  class="snav-item flex items-center gap-2.5 px-3 py-2 rounded-r-lg text-sm text-gray-400">💬 Discord</a>
                <div class="text-xs font-semibold text-red-800 uppercase tracking-widest px-3 py-2 mt-2">Danger</div>
                <a href="#section-danger"   class="snav-item flex items-center gap-2.5 px-3 py-2 rounded-r-lg text-sm text-red-400">⚠️ {{ __('messages.delete_account') }}</a>
            </div>
        </nav>

        <div class="md:col-span-3 flex flex-col gap-5">

            {{-- AVATAR --}}
            <div class="scard" id="section-avatar">
                <div class="scard-head">
                    <span class="text-lg">🖼️</span>
                    <div>
                        <h2 class="text-sm font-semibold text-white" style="font-family:'Rajdhani',sans-serif;">{{ __('messages.avatar') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, GIF · max. 2 MB · S3</p>
                    </div>
                </div>
                <div class="scard-body">
                    <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" id="avForm">
                        @csrf
                        <div class="flex items-start gap-5 flex-wrap">
                            <div class="flex-shrink-0 text-center">
                                <div class="w-20 h-20 rounded-full p-[3px]" style="background:linear-gradient(135deg,#f59e0b,#ea580c);box-shadow:0 0 20px rgba(245,158,11,.25);">
                                    <img id="avPreview"
                                         src="{{ auth()->user()->avatar ? \Storage::disk('s3')->url(auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=d97706&color=000&size=200&bold=true' }}"
                                         class="w-full h-full rounded-full object-cover block bg-gray-900" alt="Avatar">
                                </div>
                                @if(auth()->user()->avatar)
                                <form method="POST" action="{{ route('profile.avatar.delete') }}" class="mt-2 inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-300 transition-colors">🗑 {{ __('messages.remove') }}</button>
                                </form>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div id="avDropZone" class="rounded-lg p-5 text-center" style="border:2px dashed #374151;cursor:pointer;"
                                     onclick="document.getElementById('avInput').click()">
                                    <div id="avDropContent">
                                        <div class="text-2xl mb-1.5">📁</div>
                                        <p class="text-sm text-gray-400">{{ __('messages.drag_drop') }} <span class="text-amber-400 font-semibold">{{ __('messages.browse') }}</span></p>
                                        <p class="text-xs text-gray-600 mt-0.5">JPG · PNG · GIF · max. 2 MB</p>
                                    </div>
                                    <div id="avSelectedInfo" style="display:none;" class="text-sm text-amber-400 font-semibold"></div>
                                </div>
                                <input type="file" id="avInput" name="avatar" accept="image/jpeg,image/png,image/gif" style="display:none;"
                                       onchange="
                                           var f=this.files[0];
                                           if(!f) return;
                                           var r=new FileReader();
                                           r.onload=function(ev){document.getElementById('avPreview').src=ev.target.result;};
                                           r.readAsDataURL(f);
                                           document.getElementById('avDropContent').style.display='none';
                                           document.getElementById('avSelectedInfo').style.display='block';
                                           document.getElementById('avSelectedInfo').textContent='📎 '+f.name;
                                           document.getElementById('avActionBar').style.display='flex';
                                       ">
                                @error('avatar')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                <div id="avActionBar" style="display:none;margin-top:10px;gap:8px;align-items:center;">
                                    <button type="submit" style="background:linear-gradient(135deg,#f59e0b,#ea580c);color:#000;padding:8px 18px;border-radius:6px;font-size:13px;font-weight:700;border:none;cursor:pointer;font-family:'Rajdhani',sans-serif;">
                                        ⬆ {{ __('messages.upload') }}
                                    </button>
                                    <button type="button" style="color:#9ca3af;padding:8px 14px;border-radius:6px;font-size:13px;border:1px solid #4b5563;background:transparent;cursor:pointer;"
                                            onclick="document.getElementById('avInput').value='';document.getElementById('avDropContent').style.display='block';document.getElementById('avSelectedInfo').style.display='none';document.getElementById('avActionBar').style.display='none';">
                                        {{ __('messages.cancel') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- PUBLIC PROFILE --}}
            <div class="scard" id="section-profile">
                <div class="scard-head">
                    <span class="text-lg">👤</span>
                    <div>
                        <h2 class="text-sm font-semibold text-white" style="font-family:'Rajdhani',sans-serif;">{{ __('messages.public_profile') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ __('messages.public_profile_hint') }}</p>
                    </div>
                </div>
                <div class="scard-body">
                    <form method="POST" action="{{ route('profile.settings.update') }}" id="profileForm">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ __('messages.display_name') }}</label>
                                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="ifield" oninput="markDirty()">
                                @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">E-Mail</label>
                                <input type="email" value="{{ auth()->user()->email }}" disabled class="ifield">
                                <p class="text-xs text-gray-600 mt-1">{{ __('messages.email_hint') }}</p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Bio</label>
                            <textarea name="bio" rows="3" maxlength="1000" class="ifield" style="resize:vertical;" oninput="updateCC(this,'bioCC',1000);markDirty()">{{ old('bio', auth()->user()->bio) }}</textarea>
                            <div class="flex justify-end mt-0.5"><span class="text-xs font-mono text-gray-600" id="bioCC">{{ strlen(auth()->user()->bio ?? '') }} / 1000</span></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Website</label>
                                <input type="url" name="website" value="{{ old('website', auth()->user()->website) }}" placeholder="https://" class="ifield" oninput="markDirty()">
                                @error('website')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Discord</label>
                                <input type="text" name="discord_username" value="{{ old('discord_username', auth()->user()->discord_username) }}" placeholder="Username" class="ifield" oninput="markDirty()">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Telegram</label>
                                <input type="text" name="telegram_username" value="{{ old('telegram_username', auth()->user()->telegram_username) }}" placeholder="@username" class="ifield" oninput="markDirty()">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Clan / Team</label>
                                <input type="text" name="clan" value="{{ old('clan', auth()->user()->clan) }}" placeholder="z.B. |ETI|Clan" class="ifield" oninput="markDirty()">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Lieblingsspiele</label>
                            <div class="flex flex-col gap-2">
                                @foreach(['et' => 'Wolfenstein: ET', 'rtcw' => 'Return to Castle Wolfenstein', 'etl' => 'ET: Legacy'] as $val => $label)
                                <label class="flex items-center gap-2.5 cursor-pointer" onclick="markDirty()">
                                    <input type="checkbox" name="favorite_games[]" value="{{ $val }}"
                                           {{ in_array($val, auth()->user()->favorite_games ?? []) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded border-gray-600 bg-gray-800 accent-amber-500">
                                    <span class="text-sm text-gray-300 uppercase tracking-wide font-semibold">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ __('messages.language') }}</label>
                            <select name="locale" class="ifield" onchange="markDirty()">
                                @foreach(config('languages', []) as $code => $lang)
                                    @if(is_dir(lang_path($code)))
                                    <option value="{{ $code }}" {{ auth()->user()->locale === $code ? 'selected' : '' }}>{{ $lang['flag'] ?? '' }} {{ $lang['name'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div id="saveBar" class="hidden -mx-5 -mb-5 px-5 py-3 border-t border-gray-700 flex items-center justify-between" style="background:rgba(17,24,39,.97);">
                            <span class="text-xs text-amber-400 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>{{ __('messages.unsaved_changes') }}</span>
                            <div class="flex gap-2">
                                <button type="button" onclick="discardChanges()" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-400 border border-gray-600 hover:border-gray-400 transition-all">{{ __('messages.discard') }}</button>
                                <button type="submit" class="inline-flex items-center gap-1 px-4 py-1.5 rounded-lg text-xs font-semibold text-black" style="background:linear-gradient(135deg,#f59e0b,#ea580c);font-family:'Rajdhani',sans-serif;">💾 {{ __('messages.save_changes') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- NOTIFICATIONS --}}
            <div class="scard" id="section-notifs">
                <div class="scard-head">
                    <span class="text-lg">🔔</span>
                    <div><h2 class="text-sm font-semibold text-white" style="font-family:'Rajdhani',sans-serif;">{{ __('messages.notifications') }}</h2></div>
                </div>
                <div class="scard-body">
                    @php $notifPrefs = auth()->user()->notification_preferences ?? []; @endphp
                    <div class="flex flex-col divide-y divide-gray-700">
                        @foreach([
                            ['key'=>'comments',  'icon'=>'💬','label'=>__('messages.notif_comments'),  'desc'=>__('messages.notif_comments_desc')],
                            ['key'=>'downloads', 'icon'=>'⬇', 'label'=>__('messages.notif_downloads'), 'desc'=>__('messages.notif_downloads_desc')],
                            ['key'=>'ratings',   'icon'=>'⭐','label'=>__('messages.notif_ratings'),   'desc'=>__('messages.notif_ratings_desc')],
                            ['key'=>'telegram',  'icon'=>'✈️','label'=>'Telegram Bot',                 'desc'=>__('messages.notif_telegram_desc')],
                            ['key'=>'newsletter','icon'=>'📰','label'=>__('messages.notif_newsletter'),'desc'=>__('messages.notif_newsletter_desc')],
                        ] as $n)
                        <div class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-200">{{ $n['icon'] }} {{ $n['label'] }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $n['desc'] }}</div>
                            </div>
                            <label class="tog">
                                <input type="checkbox" {{ ($notifPrefs[$n['key']] ?? false) ? 'checked' : '' }} onchange="saveNotif('{{ $n['key'] }}', this.checked)">
                                <span class="tog-tr"></span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- PASSWORD --}}
            <div class="scard" id="section-password">
                <div class="scard-head">
                    <span class="text-lg">🔐</span>
                    <div>
                        <h2 class="text-sm font-semibold text-white" style="font-family:'Rajdhani',sans-serif;">{{ __('messages.change_password') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ __('messages.password_hint') }}</p>
                    </div>
                </div>
                <div class="scard-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf @method('PUT')
                        <div class="flex flex-col gap-4 max-w-md">
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ __('messages.current_password') }}</label>
                                <input type="password" name="current_password" required class="ifield">
                                @error('current_password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ __('messages.new_password') }}</label>
                                <input type="password" name="password" required class="ifield" oninput="pwStr(this.value)">
                                <div class="pw-bar mt-1.5"><div class="pw-fill" id="pwFill" style="width:0%;background:#ef4444"></div></div>
                                <div class="text-xs text-gray-600 mt-0.5" id="pwLabel">Bitte Passwort eingeben</div>
                                @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ __('messages.confirm_password') }}</label>
                                <input type="password" name="password_confirmation" required class="ifield">
                            </div>
                        </div>
                        <button type="submit" class="mt-4 inline-flex items-center gap-1.5 px-5 py-2 rounded-lg text-sm font-semibold text-black" style="background:linear-gradient(135deg,#f59e0b,#ea580c);font-family:'Rajdhani',sans-serif;">🔐 {{ __('messages.update_password') }}</button>
                    </form>
                </div>
            </div>

            {{-- DISCORD --}}
            <div class="scard" id="section-discord">
                <div class="scard-head">
                    <svg class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                    <div>
                        <h2 class="text-sm font-semibold text-white" style="font-family:'Rajdhani',sans-serif;">Discord</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Verbinde deinen Discord Account.</p>
                    </div>
                </div>
                <div class="scard-body">
                    @if(auth()->user()->discord_id)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-300">Connected as <strong class="text-indigo-400">{{ auth()->user()->discord_username }}</strong></span>
                            <a href="{{ route('auth.discord.disconnect') }}" class="text-xs text-red-400 hover:text-red-300">Disconnect</a>
                        </div>
                    @else
                        <a href="{{ route('auth.discord.redirect') }}" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                            Connect Discord
                        </a>
                    @endif
                </div>
            </div>

            {{-- DANGER --}}
            <div class="scard" style="border-color:rgba(239,68,68,.3);" id="section-danger">
                <div class="scard-head" style="background:rgba(239,68,68,.05);border-bottom-color:rgba(239,68,68,.2);">
                    <span class="text-lg">⚠️</span>
                    <div>
                        <h2 class="text-sm font-semibold" style="color:#f87171;font-family:'Rajdhani',sans-serif;">{{ __('messages.danger_zone') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ __('messages.danger_hint') }}</p>
                    </div>
                </div>
                <div class="scard-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</div>

<script>
var origVals={};
document.querySelectorAll('#profileForm input[name],#profileForm textarea[name],#profileForm select[name]').forEach(function(el){origVals[el.name]=el.value;});
function markDirty(){var sb=document.getElementById('saveBar');if(sb)sb.classList.remove('hidden');}
function discardChanges(){Object.keys(origVals).forEach(function(n){var el=document.querySelector('#profileForm [name="'+n+'"]');if(el)el.value=origVals[n];});var sb=document.getElementById('saveBar');if(sb)sb.classList.add('hidden');}
function updateCC(el,id,max){var cc=document.getElementById(id);if(!cc)return;cc.textContent=el.value.length+' / '+max;cc.style.color=el.value.length>max*.85?'#f59e0b':'#4b5563';}
function avPreview(e){var f=e.target.files[0];if(!f)return;var r=new FileReader();r.onload=function(ev){document.getElementById('avPreview').src=ev.target.result;document.getElementById("avActions").style.display="flex";};r.readAsDataURL(f);}
function avReset(){document.getElementById('avInput').value='';document.getElementById("avActions").style.display="none";}
function avDrop(e){e.preventDefault();e.currentTarget.classList.remove('dragover');var f=e.dataTransfer.files[0];if(!f)return;try{var dt=new DataTransfer();dt.items.add(f);document.getElementById('avInput').files=dt.files;}catch(err){}avPreview({target:{files:[f]}});}
function pwStr(pw){var s=0;if(pw.length>=8)s++;if(pw.length>=12)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;var cols=['#ef4444','#ef4444','#f59e0b','#f59e0b','#22c55e','#22c55e'];var labs=['Zu kurz','Sehr schwach','Schwach','Mittel','Stark','Sehr stark'];var ws=[0,20,40,60,80,100];var i=Math.min(s,5);var f=document.getElementById('pwFill');var l=document.getElementById('pwLabel');if(f)f.style.cssText='width:'+ws[i]+'%;background:'+cols[i];if(l)l.textContent=pw.length===0?'Bitte Passwort eingeben':labs[i];}
function saveNotif(key,val){
    var fd=new FormData();
    fd.append('_token','{{ csrf_token() }}');
    fd.append('key',key);
    fd.append('value',val?'1':'0');
    fetch('{{ route("profile.notifications.update") }}',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){if(d.ok){var el=document.getElementById('notif-'+key);if(el){el.style.color='#22c55e';setTimeout(function(){el.style.color='';},1500);}}})
    .catch(function(e){console.error('notif error',e);});
}
document.querySelectorAll('.snav-item[href^="#"]').forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();document.querySelectorAll('.snav-item').forEach(function(x){x.classList.remove('active');x.style.color='';});a.classList.add('active');var t=document.querySelector(a.getAttribute('href'));if(t)t.scrollIntoView({behavior:'smooth',block:'start'});});});
</script>
</x-layouts.app>