<x-layouts.app :title="$file->title" :seo="$seo ?? []" :jsonLd="$jsonLd ?? []">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
{{-- Breadcrumb --}}
<nav class="text-sm text-gray-400 mb-6">
<a href="{{ route('home') }}" class="hover:text-amber-400">{{ __('messages.home') }}</a> /
<a href="{{ route('files.index') }}" class="hover:text-amber-400">{{ __('messages.files') }}</a> /
@if($file->category?->parent)
<a href="{{ route('categories.show', $file->category->parent) }}" class="hover:text-amber-400">{{ $file->category->parent->name }}</a> /
@endif
<a href="{{ route('categories.show', $file->category) }}" class="hover:text-amber-400">{{ $file->category?->name }}</a> /
<span class="text-gray-300">{{ $file->display_title }}</span>
</nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2">
            {{-- Screenshots --}}
            @if($file->screenshots->isNotEmpty())
                <div class="mb-6">
                    <img src="{{ $file->primary_image_url }}" alt="{{ $file->display_title }}"
                         class="w-full rounded-lg border border-gray-700" id="mainImage" loading="lazy" style="aspect-ratio: 16/9;">
                    @if($file->screenshots->count() > 1)
                        <div class="flex space-x-2 mt-4 overflow-x-auto">
                            @foreach($file->screenshots as $screenshot)
                                <img src="{{ $screenshot->thumbnail_url }}" alt=""
                                     class="w-24 h-16 object-cover rounded cursor-pointer border-2 border-transparent hover:border-amber-400" width="96" height="64"
                                     loading="lazy"
                                     onclick="document.getElementById('mainImage').src='{{ $screenshot->url }}'">
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <h1 class="text-3xl font-bold text-white mb-4">{{ $file->display_title }}</h1>

            {{-- 3D Map Preview Button --}}
            @if($file->bsp_path)
                <div class="mb-6" x-data="{ bspOpen: false }">
                    <button @click="bspOpen = true"
                            class="group flex items-center space-x-3 w-full bg-gradient-to-r from-gray-800 to-gray-800/80 hover:from-amber-900/30 hover:to-gray-800 border border-gray-700 hover:border-amber-600/50 rounded-lg px-5 py-4 transition-all duration-300">
                        <div class="w-10 h-10 bg-amber-500/15 group-hover:bg-amber-500/25 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                                <path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/>
                            </svg>
                        </div>
                        <div class="text-left flex-1">
                            <div class="text-amber-400 font-semibold text-sm group-hover:text-amber-300 transition-colors">
                                Interactive 3D Map Preview
                            </div>
                            <div class="text-gray-500 text-xs mt-0.5">
                                {{ $file->map_name }} — WASD to move, mouse to look
                            </div>
                        </div>
                        <div class="text-gray-600 group-hover:text-amber-400/60 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </button>

                    {{-- 3D Viewer Modal --}}
                    <div x-show="bspOpen" x-cloak
                         class="fixed inset-0 z-50 bg-black/95"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @keydown.escape.window="bspOpen = false">

                        {{-- Top Bar --}}
                        <div class="absolute top-0 left-0 right-0 z-10 bg-gradient-to-b from-black/80 to-transparent px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-white font-bold text-sm">{{ $file->map_name }}</h3>
                                        <p class="text-gray-500 text-xs">3D Map Preview — BSP v46 WebGL</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span id="bsp-fps" class="text-green-400 text-xs font-mono bg-green-900/30 px-2 py-1 rounded border border-green-800/50">-- FPS</span>
                                    <button id="bsp-wire-btn" onclick="toggleWireframe()"
                                            class="text-gray-400 hover:text-amber-400 text-xs font-semibold bg-gray-800/50 hover:bg-gray-700/50 px-3 py-1.5 rounded border border-gray-700 transition-colors">
                                        WIREFRAME
                                    </button>
                                    <button @click="bspOpen = false; destroyBspViewer()"
                                            class="text-gray-400 hover:text-white bg-gray-800/50 hover:bg-gray-700/50 w-8 h-8 rounded-lg flex items-center justify-center border border-gray-700 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- 3D Canvas Container --}}
                        <div id="bsp-viewer-container" class="w-full h-full"
                             x-init="$watch('bspOpen', value => { if(value) setTimeout(initBspViewer, 100) })">

                            {{-- Loading State --}}
                            <div id="bsp-loading" class="absolute inset-0 flex flex-col items-center justify-center">
                                <div class="w-16 h-16 border-2 border-amber-500/30 rounded-lg mb-6 flex items-center justify-center animate-pulse">
                                    <svg class="w-8 h-8 text-amber-400 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                                        <path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/>
                                    </svg>
                                </div>
                                <div class="text-white font-semibold mb-2">Loading {{ $file->map_name }}.bsp</div>
                                <div class="w-64 h-1.5 bg-gray-800 rounded-full overflow-hidden mb-2">
                                    <div id="bsp-progress-bar" class="h-full bg-gradient-to-r from-amber-500 to-amber-400 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <div id="bsp-progress-text" class="text-gray-500 text-xs">Initializing WebGL...</div>
                            </div>
                        </div>

                        {{-- Controls Overlay (bottom-left) --}}
                        {{-- Controls handled by JS overlay --}}

                        {{-- Wolffiles branding (bottom-right) --}}
                        <div class="absolute bottom-6 right-6 text-xs text-gray-600 z-10">
                            Powered by <span class="text-amber-500/60 font-bold">WOLFFILES.EU</span>
                        </div>
                    </div>
                </div>

                {{-- 3D Viewer Script --}}
                @push('scripts')
                    <script src="/js/bsp-viewer/game-shim.js"></script>
                    <script src="/js/bsp-viewer/gl-matrix-min.js"></script>
                    <script src="/js/bsp-viewer/q3shader.js"></script>
                    <script src="/js/bsp-viewer/q3glshader.js"></script>
                    <script src="/js/bsp-viewer/q3bsp.js"></script>
                    <script src="/js/bsp-viewer/q3movement.js"></script>
                    <script>
                    // Wolffiles.eu BSP 3D Map Viewer — Optimized Controls v2
                    var bspViewer = {
                        map: null,
                        mover: null,
                        gl: null,
                        canvas: null,
                        active: false,
                        yaw: 3,
                        pitch: 0,
                        cameraPosition: [0, 0, 0],
                        velocity: [0, 0, 0],
                        keys: {},
                        lastTime: 0,
                        frameCount: 0,
                        fpsTime: 0,
                        mouseSensitivity: 0.003,
                        moveSpeed: 400,
                        sprintMultiplier: 2.5,
                        friction: 8,
                        acceleration: 12,
                        pointerLocked: false,
                        showHelp: true,
                        noclip: true,
                        farClip: 4096,
                        isMobile: false,
                        touchLook: { active: false, id: null, lastX: 0, lastY: 0 },
                        joystick: { active: false, id: null, startX: 0, startY: 0, dx: 0, dy: 0 },
                        noclip: true,
                        farClip: 4096
                    };

                    function initBspViewer() {
                        var container = document.getElementById('bsp-viewer-container');
                        if (bspViewer.active) return;

                        // Create canvas
                        bspViewer.canvas = document.createElement('canvas');
                        bspViewer.canvas.id = 'bsp-canvas';
                        bspViewer.canvas.style.width = '100%';
                        bspViewer.canvas.style.height = '100%';
                        bspViewer.canvas.style.cursor = 'pointer';
                        bspViewer.canvas.width = container.clientWidth;
                        bspViewer.canvas.height = container.clientHeight;
                        container.appendChild(bspViewer.canvas);

                        // Mobile detection (early)
                        bspViewer.isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) || ('ontouchstart' in window && window.innerWidth < 1024);

                        // Crosshair overlay
                        var crosshair = document.createElement('div');
                        crosshair.id = 'bsp-crosshair';
                        crosshair.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;z-index:10;display:none;';
                        if (bspViewer.isMobile) crosshair.style.display = 'none';
                        crosshair.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2" fill="none" stroke="rgba(255,191,0,0.8)" stroke-width="1.5"/><line x1="12" y1="4" x2="12" y2="9" stroke="rgba(255,191,0,0.6)" stroke-width="1.5"/><line x1="12" y1="15" x2="12" y2="20" stroke="rgba(255,191,0,0.6)" stroke-width="1.5"/><line x1="4" y1="12" x2="9" y2="12" stroke="rgba(255,191,0,0.6)" stroke-width="1.5"/><line x1="15" y1="12" x2="20" y2="12" stroke="rgba(255,191,0,0.6)" stroke-width="1.5"/></svg>';
                        container.appendChild(crosshair);

                        // Click-to-start overlay
                        var clickOverlay = document.createElement('div');
                        clickOverlay.id = 'bsp-click-overlay';
                        clickOverlay.style.cssText = 'position:absolute;inset:0;display:none;align-items:center;justify-content:center;z-index:20;background:rgba(0,0,0,0.5);cursor:pointer;';
                        clickOverlay.innerHTML = '<div style="text-align:center;"><div style="font-size:48px;margin-bottom:12px;">🎮</div><div style="color:#fbbf24;font-size:18px;font-weight:600;">Click to look around</div><div style="color:#9ca3af;font-size:13px;margin-top:6px;">Press ESC to release mouse</div></div>';
                        clickOverlay.addEventListener('click', function() {
                            if(bspViewer.isMobile){clickOverlay.style.display='none';bspViewer.pointerLocked=true;var tui=document.getElementById('bsp-touch-ui');if(tui)tui.style.display='block';}else{bspViewer.canvas.requestPointerLock();}
                        });
                        container.appendChild(clickOverlay);

                        // Help overlay
                        var helpOverlay = document.createElement('div');
                        helpOverlay.id = 'bsp-help';
                        helpOverlay.style.cssText = 'position:absolute;bottom:16px;left:16px;z-index:15;pointer-events:none;';
                        helpOverlay.innerHTML = '<div style="background:rgba(0,0,0,0.75);border:1px solid rgba(251,191,36,0.3);border-radius:8px;padding:10px 14px;font-size:11px;color:#d1d5db;line-height:1.8;">'
                            + '<div style="color:#fbbf24;font-weight:600;margin-bottom:4px;font-size:12px;">Controls</div>'
                            + '<div><span style="color:#fbbf24;font-family:monospace;">W A S D</span> — Move</div>'
                            + '<div><span style="color:#fbbf24;font-family:monospace;">Mouse</span> — Look around</div>'
                            + '<div><span style="color:#fbbf24;font-family:monospace;">Space</span> — Fly up · <span style="color:#fbbf24;font-family:monospace;">C</span> — Fly down</div>'
                            + '<div><span style="color:#fbbf24;font-family:monospace;">Shift</span> — Sprint · <span style="color:#fbbf24;font-family:monospace;">Scroll</span> — Speed</div>'
                            + '<div><span style="color:#fbbf24;font-family:monospace;">V</span> — Noclip · <span style="color:#fbbf24;font-family:monospace;">ESC</span> — Release &middot; <span style="color:#fbbf24;font-family:monospace;">+/-</span> Draw dist</div>'
                            + '</div>';
                        if (!/Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)) container.appendChild(helpOverlay);

                        // Speed indicator
                        var speedIndicator = document.createElement('div');
                        speedIndicator.id = 'bsp-speed';
                        speedIndicator.style.cssText = 'position:absolute;top:128px;right:16px;z-index:15;pointer-events:none;background:rgba(0,0,0,0.6);border:1px solid rgba(107,114,128,0.3);border-radius:6px;padding:6px 10px;font-size:11px;color:#9ca3af;font-family:monospace;';
                        speedIndicator.textContent = 'Speed: 400';
                        if (!bspViewer.isMobile) container.appendChild(speedIndicator);

                        var nI=document.createElement('div');nI.id='bsp-noclip';nI.style.cssText='position:absolute;top:72px;right:16px;z-index:15;pointer-events:none;background:rgba(0,0,0,0.6);border:1px solid rgba(234,179,8,0.3);border-radius:6px;padding:6px 10px;font-size:11px;color:#facc15;font-family:monospace;';nI.textContent='Noclip: ON';if(!bspViewer.isMobile)container.appendChild(nI);
                        var cI=document.createElement('div');cI.id='bsp-clip';cI.style.cssText='position:absolute;top:100px;right:16px;z-index:15;pointer-events:none;background:rgba(0,0,0,0.6);border:1px solid rgba(34,211,238,0.3);border-radius:6px;padding:6px 10px;font-size:11px;color:#22d3ee;font-family:monospace;';cI.textContent='Draw: 4096';if(!bspViewer.isMobile)container.appendChild(cI);

                        if(bspViewer.isMobile){
                            var rO=document.createElement('div');rO.id='bsp-rotate';rO.style.cssText='position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,1);display:flex;align-items:center;justify-content:center;flex-direction:column;';
                            rO.innerHTML='<div style="font-size:64px;animation:bR 2s ease-in-out infinite">\u{1F4F1}</div><div style="color:#fbbf24;font-size:18px;font-weight:600;margin-top:16px">Rotate your device</div><div style="color:#9ca3af;font-size:13px;margin-top:8px">Landscape mode required</div><button id="bsp-rotate-exit" style="margin-top:24px;padding:10px 28px;border-radius:10px;background:rgba(220,38,38,0.6);border:2px solid rgba(239,68,68,0.6);color:#fca5a5;font-size:14px;font-weight:600;font-family:monospace;touch-action:none;">EXIT VIEWER</button><style>@keyframes bR{0%,100%{transform:rotate(0)}25%,50%{transform:rotate(-90deg)}75%{transform:rotate(0)}}</style>';
                            container.appendChild(rO);
                            document.getElementById('bsp-rotate-exit').addEventListener('touchstart',function(e){e.preventDefault();var cl=document.querySelector('[x-data]');if(cl&&cl.__x){cl.__x.$data.bspOpen=false;}if(typeof destroyBspViewer==='function')destroyBspViewer();},{passive:false});
                            function chkO(){var r=document.getElementById('bsp-rotate');if(r)r.style.display=(window.innerWidth<window.innerHeight)?'flex':'none';}
                            window.addEventListener('resize',chkO);window.addEventListener('orientationchange',function(){setTimeout(chkO,200);});chkO();

                            var tU=document.createElement('div');tU.id='bsp-touch-ui';tU.style.cssText='position:absolute;inset:0;z-index:30;pointer-events:none;display:none;';
                            tU.innerHTML='<div id="bsp-joy-zone" style="position:absolute;left:0;top:0;width:40%;height:100%;pointer-events:auto;touch-action:none;"><div id="bsp-joy-base" style="display:none;position:absolute;width:120px;height:120px;border-radius:50%;border:2px solid rgba(251,191,36,0.4);background:rgba(0,0,0,0.3)"></div><div id="bsp-joy-stick" style="display:none;position:absolute;width:50px;height:50px;border-radius:50%;background:rgba(251,191,36,0.6);border:2px solid rgba(251,191,36,0.8)"></div></div><div id="bsp-look-zone" style="position:absolute;right:0;top:0;width:60%;height:100%;pointer-events:auto;touch-action:none;"></div><div style="position:absolute;right:70px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:8px;pointer-events:auto;z-index:31;"><button id="bsp-btn-up" style="width:44px;height:44px;border-radius:10px;background:rgba(0,0,0,0.5);border:2px solid rgba(251,191,36,0.4);color:#fbbf24;font-size:20px;touch-action:none;">&#9650;</button><button id="bsp-btn-down" style="width:44px;height:44px;border-radius:10px;background:rgba(0,0,0,0.5);border:2px solid rgba(251,191,36,0.4);color:#fbbf24;font-size:20px;touch-action:none;">&#9660;</button></div><div style="position:absolute;bottom:56px;left:50%;transform:translateX(-50%);display:flex;gap:8px;pointer-events:auto;z-index:31;"><button id="bsp-btn-exit" style="height:36px;padding:0 12px;border-radius:10px;background:rgba(220,38,38,0.5);border:2px solid rgba(239,68,68,0.6);color:#fca5a5;font-size:12px;font-weight:600;font-family:monospace;touch-action:none;">EXIT</button><button id="bsp-btn-sprint" style="height:36px;padding:0 12px;border-radius:10px;background:rgba(0,0,0,0.5);border:2px solid rgba(107,114,128,0.4);color:#9ca3af;font-size:12px;font-weight:600;font-family:monospace;touch-action:none;">SPRINT</button><button id="bsp-btn-noclip" style="height:36px;padding:0 12px;border-radius:10px;background:rgba(0,0,0,0.5);border:2px solid rgba(234,179,8,0.4);color:#facc15;font-size:12px;font-weight:600;font-family:monospace;touch-action:none;">NOCLIP</button><button id="bsp-btn-clipplus" style="height:36px;padding:0 10px;border-radius:10px;background:rgba(0,0,0,0.5);border:2px solid rgba(34,211,238,0.4);color:#22d3ee;font-size:14px;font-weight:600;font-family:monospace;touch-action:none;">DRAW+</button><button id="bsp-btn-clipminus" style="height:36px;padding:0 10px;border-radius:10px;background:rgba(0,0,0,0.5);border:2px solid rgba(34,211,238,0.4);color:#22d3ee;font-size:14px;font-weight:600;font-family:monospace;touch-action:none;">DRAW-</button></div>';
                            container.appendChild(tU);

                            var jZ=document.getElementById('bsp-joy-zone'),jB=document.getElementById('bsp-joy-base'),jS=document.getElementById('bsp-joy-stick');
                            jZ.addEventListener('touchstart',function(e){e.preventDefault();var t=e.changedTouches[0],r=jZ.getBoundingClientRect();bspViewer.joystick.active=true;bspViewer.joystick.id=t.identifier;bspViewer.joystick.startX=t.clientX-r.left;bspViewer.joystick.startY=t.clientY-r.top;jB.style.display='block';jB.style.left=(bspViewer.joystick.startX-60)+'px';jB.style.top=(bspViewer.joystick.startY-60)+'px';jS.style.display='block';jS.style.left=(bspViewer.joystick.startX-25)+'px';jS.style.top=(bspViewer.joystick.startY-25)+'px';},{passive:false});
                            jZ.addEventListener('touchmove',function(e){e.preventDefault();for(var i=0;i<e.changedTouches.length;i++){var t=e.changedTouches[i];if(t.identifier===bspViewer.joystick.id){var r=jZ.getBoundingClientRect(),dx=(t.clientX-r.left)-bspViewer.joystick.startX,dy=(t.clientY-r.top)-bspViewer.joystick.startY,d=Math.sqrt(dx*dx+dy*dy),mx=50;if(d>mx){dx=dx/d*mx;dy=dy/d*mx;}bspViewer.joystick.dx=dx/mx;bspViewer.joystick.dy=dy/mx;jS.style.left=(bspViewer.joystick.startX+dx-25)+'px';jS.style.top=(bspViewer.joystick.startY+dy-25)+'px';}}},{passive:false});
                            function rJ(){bspViewer.joystick.active=false;bspViewer.joystick.id=null;bspViewer.joystick.dx=0;bspViewer.joystick.dy=0;jB.style.display='none';jS.style.display='none';}
                            jZ.addEventListener('touchend',function(e){for(var i=0;i<e.changedTouches.length;i++){if(e.changedTouches[i].identifier===bspViewer.joystick.id)rJ();}},{passive:false});
                            jZ.addEventListener('touchcancel',rJ,{passive:false});

                            var lZ=document.getElementById('bsp-look-zone');
                            lZ.addEventListener('touchstart',function(e){e.preventDefault();var t=e.changedTouches[0];bspViewer.touchLook.active=true;bspViewer.touchLook.id=t.identifier;bspViewer.touchLook.lastX=t.clientX;bspViewer.touchLook.lastY=t.clientY;},{passive:false});
                            lZ.addEventListener('touchmove',function(e){e.preventDefault();for(var i=0;i<e.changedTouches.length;i++){var t=e.changedTouches[i];if(t.identifier===bspViewer.touchLook.id){var dx=t.clientX-bspViewer.touchLook.lastX,dy=t.clientY-bspViewer.touchLook.lastY;bspViewer.yaw-=dx*0.004;bspViewer.pitch=Math.max(-Math.PI/2+0.01,Math.min(Math.PI/2-0.01,bspViewer.pitch+dy*0.004));bspViewer.touchLook.lastX=t.clientX;bspViewer.touchLook.lastY=t.clientY;}}},{passive:false});
                            lZ.addEventListener('touchend',function(e){for(var i=0;i<e.changedTouches.length;i++){if(e.changedTouches[i].identifier===bspViewer.touchLook.id){bspViewer.touchLook.active=false;bspViewer.touchLook.id=null;}}},{passive:false});

                            var bU=document.getElementById('bsp-btn-up'),bD=document.getElementById('bsp-btn-down');
                            bU.addEventListener('touchstart',function(e){e.preventDefault();bspViewer.keys[' ']=true;},{passive:false});bU.addEventListener('touchend',function(e){e.preventDefault();bspViewer.keys[' ']=false;},{passive:false});
                            bD.addEventListener('touchstart',function(e){e.preventDefault();bspViewer.keys['c']=true;},{passive:false});bD.addEventListener('touchend',function(e){e.preventDefault();bspViewer.keys['c']=false;},{passive:false});
                            document.getElementById('bsp-btn-sprint').addEventListener('touchstart',function(e){e.preventDefault();bspViewer.keys['shift']=!bspViewer.keys['shift'];this.style.borderColor=bspViewer.keys['shift']?'rgba(251,191,36,0.8)':'rgba(107,114,128,0.4)';this.style.color=bspViewer.keys['shift']?'#fbbf24':'#9ca3af';},{passive:false});
                            document.getElementById('bsp-btn-noclip').addEventListener('touchstart',function(e){e.preventDefault();bspViewer.noclip=!bspViewer.noclip;var el=document.getElementById('bsp-noclip');if(el)el.textContent='Noclip: '+(bspViewer.noclip?'ON':'OFF');this.textContent=bspViewer.noclip?'NOCLIP':'CLIP';if(!bspViewer.noclip&&bspViewer.mover){bspViewer.mover.position=[bspViewer.cameraPosition[0],bspViewer.cameraPosition[1],bspViewer.cameraPosition[2]];bspViewer.mover.velocity=[0,0,0];}},{passive:false});
                            document.getElementById('bsp-btn-clipplus').addEventListener('touchstart',function(e){e.preventDefault();bspViewer.farClip=Math.min(65536,bspViewer.farClip*2);mat4.perspective(bspViewer.projMat,72*Math.PI/180,bspViewer.canvas.width/bspViewer.canvas.height,1.0,bspViewer.farClip);var el=document.getElementById('bsp-clip');if(el)el.textContent='Draw: '+bspViewer.farClip;},{passive:false});
                            document.getElementById('bsp-btn-clipminus').addEventListener('touchstart',function(e){e.preventDefault();bspViewer.farClip=Math.max(512,bspViewer.farClip/2);mat4.perspective(bspViewer.projMat,72*Math.PI/180,bspViewer.canvas.width/bspViewer.canvas.height,1.0,bspViewer.farClip);var el=document.getElementById('bsp-clip');if(el)el.textContent='Draw: '+bspViewer.farClip;},{passive:false});
                            document.getElementById('bsp-btn-exit').addEventListener('touchstart',function(e){e.preventDefault();var tui=document.getElementById('bsp-touch-ui');if(tui)tui.style.display='none';bspViewer.pointerLocked=false;var ov=document.getElementById('bsp-click-overlay');if(ov)ov.style.display='flex';},{passive:false});
                            bspViewer.pointerLocked=true;
                        }

                        // Init WebGL
                        try {
                            bspViewer.gl = bspViewer.canvas.getContext('webgl2') || bspViewer.canvas.getContext('webgl') || bspViewer.canvas.getContext('experimental-webgl');
                        } catch(e) {}

                        if (!bspViewer.gl) {
                            document.getElementById('bsp-progress-text').textContent = 'WebGL not supported!';
                            return;
                        }

                        var gl = bspViewer.gl;
                        gl.clearColor(0.1, 0.12, 0.19, 1.0);
                        gl.clearDepth(1.0);
                        gl.enable(gl.DEPTH_TEST);
                        gl.enable(gl.BLEND);
                        gl.enable(gl.CULL_FACE);

                        // Set custom texture base for this map's S3 assets
                        window.bspCustomTextureBase = '/tex-proxy/{{ $file->id }}';

                        // Set projection matrix
                        var projMat = mat4.create();
                        mat4.perspective(projMat, 72 * Math.PI / 180, bspViewer.canvas.width / bspViewer.canvas.height, 1.0, bspViewer.farClip);
                        bspViewer.projMat = projMat;

                        // Load BSP
                        bspViewer.map = new q3bsp(gl);

                        bspViewer.map.onentitiesloaded = function(entities) {
                            var spawn = null;
                            var spawnKeys = ['info_player_deathmatch', 'info_player_start', 'team_CTF_bluespawn', 'info_player_allied', 'info_player_intermission'];
                            for (var i = 0; i < spawnKeys.length; i++) {
                                if (entities && entities[spawnKeys[i]] && entities[spawnKeys[i]][0]) {
                                    spawn = entities[spawnKeys[i]][0];
                                    break;
                                }
                            }
                            if (spawn && spawn.origin) {
                                bspViewer.cameraPosition = [
                                    parseFloat(spawn.origin[0]),
                                    parseFloat(spawn.origin[1]),
                                    parseFloat(spawn.origin[2])
                                ];
                                // Set angle from spawn if available
                                if (spawn.angle) {
                                    bspViewer.yaw = (parseFloat(spawn.angle) + 90) * Math.PI / 180;
                                }
                            }
                        };

                        bspViewer.map.onbsp = function(bsp) {
                            bspViewer.mover = new q3movement(bsp);
                            bspViewer.mover.position = bspViewer.cameraPosition;

                            var loadingEl = document.getElementById('bsp-loading');
                            if (loadingEl) loadingEl.style.display = 'none';

                            // Show click overlay
                            var overlay = document.getElementById('bsp-click-overlay');
                            if (overlay) overlay.style.display = 'flex';

                            bspViewer.active = true;
                            var container = document.getElementById('bsp-viewer-container');
                            bspViewer.canvas.width = container.clientWidth;
                            bspViewer.canvas.height = container.clientHeight;
                            bspViewer.gl.viewport(0, 0, bspViewer.canvas.width, bspViewer.canvas.height);
                            mat4.perspective(bspViewer.projMat, 72 * Math.PI / 180, bspViewer.canvas.width / bspViewer.canvas.height, 1.0, bspViewer.farClip);
                            bspViewer.lastTime = performance.now();
                            requestAnimationFrame(bspRenderLoop);
                        };

                        // Loading progress
                        var progressBar = document.getElementById('bsp-progress-bar');
                        var progressText = document.getElementById('bsp-progress-text');
                        bspViewer.map.onLoadStatus = function(msg) {
                            if (progressText) progressText.textContent = msg;
                            var pct = 10;
                            if (msg.indexOf('vertices') >= 0) pct = 30;
                            if (msg.indexOf('face') >= 0) pct = 50;
                            if (msg.indexOf('light') >= 0) pct = 70;
                            if (msg.indexOf('vis') >= 0) pct = 90;
                            if (progressBar) progressBar.style.width = pct + '%';
                        };

                        var shaderList = ['scripts/all_shaders.shader'];
                        bspViewer.map.loadShaders(shaderList);
                        bspViewer.map.load('/bsp-proxy/{{ $file->id }}', 5);

                        // === CONTROLS ===

                        // Pointer Lock
                        bspViewer.canvas.addEventListener('click', function() {
                            bspViewer.canvas.requestPointerLock();
                        });

                        document.addEventListener('pointerlockchange', function() {
                            bspViewer.pointerLocked = (document.pointerLockElement === bspViewer.canvas);
                            var crosshair = document.getElementById('bsp-crosshair');
                            var overlay = document.getElementById('bsp-click-overlay');
                            if (bspViewer.pointerLocked) {
                                bspViewer.canvas.style.cursor = 'none';
                                if (crosshair) crosshair.style.display = 'block';
                                if (overlay) overlay.style.display = 'none';
                            } else {
                                bspViewer.canvas.style.cursor = 'pointer';
                                if (crosshair) crosshair.style.display = 'none';
                                if (overlay && bspViewer.active) overlay.style.display = 'flex';
                            }
                        });

                        // Mouse look — only when pointer locked
                        // Mouse look — only when pointer locked
                        document.addEventListener('mousemove', function(e) {
                            if (!bspViewer.pointerLocked) return;
                            // Yaw: links/rechts drehen
                            bspViewer.yaw -= e.movementX * bspViewer.mouseSensitivity;
                            // Pitch: hoch/runter schauen (clamped)
                            bspViewer.pitch = Math.max(-Math.PI/2 + 0.01, Math.min(Math.PI/2 - 0.01, bspViewer.pitch + e.movementY * bspViewer.mouseSensitivity));
                        });

                        // Scroll wheel — adjust movement speed
                        bspViewer.canvas.addEventListener('wheel', function(e) {
                            e.preventDefault();
                            bspViewer.moveSpeed = Math.max(50, Math.min(2000, bspViewer.moveSpeed - e.deltaY * 0.5));
                            var speedEl = document.getElementById('bsp-speed');
                            if (speedEl) speedEl.textContent = 'Speed: ' + Math.round(bspViewer.moveSpeed);
                        }, { passive: false });

                        // Keyboard
                        window.addEventListener('keydown', function(e) {
                            var key = e.key.toLowerCase();
                            bspViewer.keys[key] = true;
                            if (['w','a','s','d',' ','arrowup','arrowdown','arrowleft','arrowright'].indexOf(key) >= 0) {
                                e.preventDefault();
                            }
                            if (key === 'v' && bspViewer.active) {
                                bspViewer.noclip = !bspViewer.noclip;
                                bspViewer.velocity = [0, 0, 0];
                                var noclipEl = document.getElementById('bsp-noclip');
                                if (noclipEl) noclipEl.textContent = 'Noclip: ' + (bspViewer.noclip ? 'ON' : 'OFF');
                                if (!bspViewer.noclip && bspViewer.mover) { bspViewer.mover.position = [bspViewer.cameraPosition[0], bspViewer.cameraPosition[1], bspViewer.cameraPosition[2]]; bspViewer.mover.velocity = [0, 0, 0]; }
                            }
                            if ((key === '+' || key === '=') && bspViewer.active) {
                                e.preventDefault(); bspViewer.farClip = Math.min(65536, bspViewer.farClip * 2);
                                mat4.perspective(bspViewer.projMat, 72 * Math.PI / 180, bspViewer.canvas.width / bspViewer.canvas.height, 1.0, bspViewer.farClip);
                                var clipEl = document.getElementById('bsp-clip'); if (clipEl) clipEl.textContent = 'Draw: ' + bspViewer.farClip;
                            }
                            if ((key === '-' || key === '_') && bspViewer.active) {
                                e.preventDefault(); bspViewer.farClip = Math.max(512, bspViewer.farClip / 2);
                                mat4.perspective(bspViewer.projMat, 72 * Math.PI / 180, bspViewer.canvas.width / bspViewer.canvas.height, 1.0, bspViewer.farClip);
                                var clipEl = document.getElementById('bsp-clip'); if (clipEl) clipEl.textContent = 'Draw: ' + bspViewer.farClip;
                            }
                        });

                        window.addEventListener('keyup', function(e) {
                            bspViewer.keys[e.key.toLowerCase()] = false;
                            if (e.key === 'Shift') bspViewer.keys['shift'] = false;
                        });

                        // Resize
                        window.addEventListener('resize', function() {
                            if (!bspViewer.canvas || !bspViewer.gl) return;
                            var container = document.getElementById('bsp-viewer-container');
                            bspViewer.canvas.width = container.clientWidth;
                            bspViewer.canvas.height = container.clientHeight;
                            bspViewer.gl.viewport(0, 0, bspViewer.canvas.width, bspViewer.canvas.height);
                            mat4.perspective(bspViewer.projMat, 72 * Math.PI / 180, bspViewer.canvas.width / bspViewer.canvas.height, 1.0, bspViewer.farClip);
                        });

                    }

                    function bspRenderLoop() {
                        if (!bspViewer.active) return;
                        requestAnimationFrame(bspRenderLoop);

                        var now = performance.now();
                        var dt = Math.min((now - bspViewer.lastTime) / 1000, 0.05);
                        bspViewer.lastTime = now;

                        // FPS counter
                        bspViewer.frameCount++;
                        bspViewer.fpsTime += dt;
                        if (bspViewer.fpsTime >= 1) {
                            var el = document.getElementById('bsp-fps');
                            if (el) el.textContent = bspViewer.frameCount + ' FPS';
                            bspViewer.frameCount = 0;
                            bspViewer.fpsTime = 0;
                        }

                        var targetSpeed = bspViewer.moveSpeed * (bspViewer.keys['shift'] ? bspViewer.sprintMultiplier : 1);

                        // === QUAKE3 COORDINATE SYSTEM ===
                        // Q3: X = forward, Y = left, Z = up
                        // yaw rotates around Z axis, pitch rotates around Y axis
                        
                        // Forward direction (HORIZONTAL only for W/S — like walking on ground)
                        var fwdX = Math.cos(bspViewer.yaw);  // Q3 X
                        var fwdY = Math.sin(bspViewer.yaw);  // Q3 Y
                        
                        // Right direction (for A/D strafe)
                        var rightX = Math.cos(bspViewer.yaw - Math.PI/2);
                        var rightY = Math.sin(bspViewer.yaw - Math.PI/2);

                        // Build wish direction from input
                        var wishDir = [0, 0, 0];
                        var hasInput = false;
                        if (bspViewer.keys["w"]) { wishDir[0] += fwdX; wishDir[1] += fwdY; hasInput = true; }
                        if (bspViewer.keys["s"]) { wishDir[0] -= fwdX; wishDir[1] -= fwdY; hasInput = true; }
                        if (bspViewer.keys["a"]) { wishDir[0] -= rightX; wishDir[1] -= rightY; hasInput = true; }
                        if (bspViewer.keys["d"]) { wishDir[0] += rightX; wishDir[1] += rightY; hasInput = true; }
                        if (bspViewer.keys[" "]) { wishDir[2] += 1; hasInput = true; }
                        if (bspViewer.keys["c"]) { wishDir[2] -= 1; hasInput = true; }
                        if (bspViewer.joystick.active && (Math.abs(bspViewer.joystick.dx) > 0.1 || Math.abs(bspViewer.joystick.dy) > 0.1)) { wishDir[0] += fwdX * (-bspViewer.joystick.dy) + rightX * bspViewer.joystick.dx; wishDir[1] += fwdY * (-bspViewer.joystick.dy) + rightY * bspViewer.joystick.dx; hasInput = true; }

                        // Normalize
                        var wishLen = Math.sqrt(wishDir[0]*wishDir[0] + wishDir[1]*wishDir[1] + wishDir[2]*wishDir[2]);
                        if (wishLen > 0) { wishDir[0] /= wishLen; wishDir[1] /= wishLen; wishDir[2] /= wishLen; }

                        // Smooth acceleration/friction
                        for (var i = 0; i < 3; i++) {
                            if (hasInput) {
                                var target = wishDir[i] * targetSpeed;
                                var diff = target - bspViewer.velocity[i];
                                bspViewer.velocity[i] += diff * Math.min(1, bspViewer.acceleration * dt);
                            } else {
                                bspViewer.velocity[i] *= Math.max(0, 1 - bspViewer.friction * dt);
                                if (Math.abs(bspViewer.velocity[i]) < 0.5) bspViewer.velocity[i] = 0;
                            }
                        }

                        // Apply velocity
                        if (bspViewer.noclip) {
                            bspViewer.cameraPosition[0] += bspViewer.velocity[0] * dt;
                            bspViewer.cameraPosition[1] += bspViewer.velocity[1] * dt;
                            bspViewer.cameraPosition[2] += bspViewer.velocity[2] * dt;
                        } else if (bspViewer.mover) {
                            bspViewer.mover.position = [bspViewer.cameraPosition[0], bspViewer.cameraPosition[1], bspViewer.cameraPosition[2]];
                            bspViewer.mover.velocity = [bspViewer.velocity[0], bspViewer.velocity[1], bspViewer.velocity[2]];
                            bspViewer.mover.move(wishDir, dt * 1000);
                            bspViewer.cameraPosition[0] = bspViewer.mover.position[0];
                            bspViewer.cameraPosition[1] = bspViewer.mover.position[1];
                            bspViewer.cameraPosition[2] = bspViewer.mover.position[2];
                        }

                        // Update BSP visibility
                        if (bspViewer.map) {
                            bspViewer.map.updateVisibility(bspViewer.cameraPosition);
                        }

                        // === BUILD VIEW MATRIX ===
                        // Quake3 coords: X=forward, Y=left, Z=up
                        // OpenGL coords: X=right, Y=up, Z=backward
                        // We need to convert Q3 -> OpenGL then apply camera rotation
                        var viewMat = mat4.create();
                        mat4.identity(viewMat);
                        
                        // 1. Apply pitch (look up/down) around X axis
                        mat4.rotateX(viewMat, viewMat, bspViewer.pitch - Math.PI/2);
                        // 2. Apply yaw (look left/right) around Z axis  
                        mat4.rotateZ(viewMat, viewMat, -bspViewer.yaw + Math.PI/2);
                        // 3. Translate to camera position (Q3 coords, +57 eye height)
                        mat4.translate(viewMat, viewMat, [-bspViewer.cameraPosition[0], -bspViewer.cameraPosition[1], -bspViewer.cameraPosition[2] - 57]);

                        // Render
                        var gl = bspViewer.gl;
                        gl.viewport(0, 0, bspViewer.canvas.width, bspViewer.canvas.height);
                        gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
                        if (bspViewer.map) {
                            bspViewer.map.draw(viewMat, bspViewer.projMat);
                        }
                    }
                    function toggleWireframe() {
                        var btn = document.getElementById('bsp-wire-btn');
                        if (btn) btn.textContent = 'N/A';
                    }

                    function destroyBspViewer() {
                        bspViewer.active = false;
                        bspViewer.pointerLocked = false;

                        if (document.pointerLockElement) {
                            document.exitPointerLock();
                        }

                        if (bspViewer.map && bspViewer.map.worker) {
                            bspViewer.map.worker.terminate();
                        }

                        var container = document.getElementById('bsp-viewer-container');

                        // Remove overlays
                        ['bsp-crosshair','bsp-click-overlay','bsp-help','bsp-speed','bsp-noclip','bsp-clip','bsp-touch-ui','bsp-rotate'].forEach(function(id) {
                            var el = document.getElementById(id);
                            if (el && el.parentNode) el.parentNode.removeChild(el);
                        });

                        if (bspViewer.canvas && container && container.contains(bspViewer.canvas)) {
                            container.removeChild(bspViewer.canvas);
                        }

                        if (bspViewer.gl) {
                            var ext = bspViewer.gl.getExtension('WEBGL_lose_context');
                            if (ext) ext.loseContext();
                        }

                        bspViewer.map = null;
                        bspViewer.mover = null;
                        bspViewer.gl = null;
                        bspViewer.canvas = null;
                        bspViewer.keys = {};
                        bspViewer.velocity = [0, 0, 0];
                        bspViewer.yaw = 3;
                        bspViewer.pitch = 0;
                        bspViewer.cameraPosition = [0, 0, 0];
                        bspViewer.moveSpeed = 400;

                        var loadingEl = document.getElementById('bsp-loading');
                        var progressBar = document.getElementById('bsp-progress-bar');
                        if (loadingEl) loadingEl.style.display = 'flex';
                        if (progressBar) progressBar.style.width = '0%';
                    }
                    </script>
                @endpush
            @endif

            {{-- Tags --}}
            @if($file->tags->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($file->tags as $tag)
                        <a href="{{ route('files.index', ['tag' => $tag->slug]) }}"
                           class="bg-gray-700 text-gray-300 px-3 py-1 rounded-full text-xs hover:bg-amber-600 hover:text-white transition-colors">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Description --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6 prose prose-invert max-w-none break-words" style="overflow-wrap: anywhere;">
                {!! $file->description_html ?? nl2br(e($file->description ?? __('messages.no_description'))) !!}
            </div>

            {{-- Share Buttons --}}
            <div class="mb-6">
                @include('components.share-buttons', ['url' => route('files.show', $file), 'title' => $file->display_title])
            </div>

            {{-- README --}}
            @if($file->readme_content)
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-amber-400 mb-4">README</h3>
                    <pre class="text-gray-300 text-sm whitespace-pre-wrap font-mono">{{ $file->readme_content }}</pre>
                </div>
            @endif


            {{-- File Contents & Virus Scan --}}
            <div class="mb-6">
                @include('components.file-contents', ['file' => $file])
            </div>

            {{-- Comments --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">{{ __('messages.comments') }} ({{ $file->comments->count() }})</h3>
                @foreach($file->comments as $comment)
                    <div class="border-b border-gray-700 py-4 last:border-0">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="font-medium text-amber-400">{{ $comment->user?->name }}</span>
                            <span class="text-gray-500 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-300">{{ $comment->body }}</p>
                    </div>
                @endforeach

                @auth
                    <form method="POST" action="{{ route('comments.store') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="commentable_type" value="App\Models\File">
                        <input type="hidden" name="commentable_id" value="{{ $file->id }}">
                        <textarea name="body" rows="3" required maxlength="2000" placeholder="{{ __('messages.write_comment') }}"
                                  class="w-full bg-gray-700 border-gray-600 text-white rounded-lg p-3 focus:ring-amber-500 focus:border-amber-500 mb-2">{{ old('body') }}</textarea>
                        @error('body') <p class="text-red-400 text-sm mb-2">{{ $message }}</p> @enderror
                        <button class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">{{ __('messages.post_comment') }}</button>
                    </form>
                @else
                    <p class="text-gray-400 mt-4">
                        <a href="{{ route('login') }}" class="text-amber-400 hover:underline">{{ __('messages.login') }}</a> {{ __('messages.to_comment') }}
                    </p>
                @endauth
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Download Box --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <a href="{{ route('files.download', $file) }}"
                   class="block w-full bg-amber-600 hover:bg-amber-700 text-white text-center py-4 rounded-lg font-bold text-lg transition-colors mb-4">
                    ↓ {{ __('messages.download') }} ({{ $file->file_size_formatted }})
                </a>

                @auth
                    <div class="flex space-x-2" x-data="{ reportOpen: false }">
                        {{-- Favorit Button --}}
                        <form method="POST" action="{{ route('files.favorite', $file) }}" class="flex-1">
                            @csrf
                            <button class="w-full flex items-center justify-center space-x-2 border {{ $isFavorited ? 'border-amber-500 text-amber-400' : 'border-gray-600 text-gray-300' }} hover:border-amber-500 hover:text-amber-400 py-2.5 rounded-lg text-sm transition-colors">
                                <svg class="w-4 h-4" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                <span>{{ $isFavorited ? __('messages.favorited') : __('messages.favorite') }}</span>
                            </button>
                        </form>

                        {{-- Melden Button --}}
                        <button @click="reportOpen = true"
                                class="flex-1 flex items-center justify-center space-x-2 border border-gray-600 text-gray-300 hover:border-red-500 hover:text-red-400 py-2.5 rounded-lg text-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                            <span>{{ __('messages.report') }}</span>
                        </button>

                        {{-- Report Modal --}}
                        <div x-show="reportOpen" x-cloak
                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
                             @click.self="reportOpen = false"
                             @keydown.escape.window="reportOpen = false">
                            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 w-full max-w-md mx-4"
                                 x-transition>
                                <h3 class="text-lg font-semibold text-white mb-4">{{ __('messages.report_title') }}</h3>

                                <form method="POST" action="{{ route('reports.store') }}">
                                    @csrf
                                    <input type="hidden" name="reportable_type" value="App\Models\File">
                                    <input type="hidden" name="reportable_id" value="{{ $file->id }}">

                                    {{-- Honeypot --}}
                                    <div class="hidden">
                                        <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.report_reason') }}</label>
                                        <select name="reason" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                                            <option value="">{{ __('messages.select') }}...</option>
                                            <option value="copyright">{{ __('messages.report_copyright') }}</option>
                                            <option value="broken">{{ __('messages.report_broken') }}</option>
                                            <option value="inappropriate">{{ __('messages.report_inappropriate') }}</option>
                                            <option value="spam">{{ __('messages.report_spam') }}</option>
                                            <option value="other">{{ __('messages.report_other') }}</option>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.report_description') }}</label>
                                        <textarea name="description" rows="3" maxlength="1000"
                                                  class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm"
                                                  placeholder="{{ __('messages.report_description_placeholder') }}"></textarea>
                                    </div>

                                    <div class="flex justify-end space-x-3">
                                        <button type="button" @click="reportOpen = false"
                                                class="px-4 py-2 text-gray-400 hover:text-white text-sm transition-colors">
                                            {{ __('messages.cancel') }}
                                        </button>
                                        <button type="submit"
                                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                                            {{ __('messages.report_submit') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>

            {{-- File Info --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">{{ __('messages.file_info') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.filename') }}</dt>
                        <dd class="text-gray-200 truncate ml-2">{{ $file->file_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.file_size') }}</dt>
                        <dd class="text-gray-200">{{ $file->file_size_formatted }}</dd>
                    </div>
                    @if($file->map_name)
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.map_name') }}</dt>
                        <dd class="text-gray-200">{{ $file->map_name }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.game') }}</dt>
                        <dd class="text-amber-400">{{ $file->game ?? '-' }}</dd>
                    </div>
                    @if($file->version)
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.version') }}</dt>
                        <dd class="text-gray-200">{{ $file->version }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.author') }}</dt>
                        <dd class="text-gray-200">{{ $file->original_author ?? $file->user?->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.downloads') }}</dt>
                        <dd class="text-gray-200">{{ number_format($file->download_count) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.uploaded') }}</dt>
                        <dd class="text-gray-200">{{ $file->published_at?->format('d.m.Y') ?? $file->created_at->format('d.m.Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.category') }}</dt>
                        <dd><a href="{{ route('categories.show', $file->category) }}" class="text-amber-400 hover:underline">{{ $file->category?->name }}</a></dd>
                    </div>
                </dl>
            </div>

            {{-- Rating --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">{{ __('messages.rating') }}</h3>
                <div class="text-center mb-4">
                    <span class="text-4xl font-bold text-amber-400">{{ number_format($file->average_rating, 1) }}</span>
                    <span class="text-gray-400">/5</span>
                    <div class="text-gray-500 text-sm">{{ $file->rating_count }} {{ __('messages.ratings') }}</div>
                </div>
                @auth
                    <form method="POST" action="{{ route('files.rate', $file) }}" class="flex justify-center space-x-1">
                        @csrf
                        @for($i = 1; $i <= 5; $i++)
                            <button name="rating" value="{{ $i }}"
                                    class="text-2xl {{ ($userRating?->rating ?? 0) >= $i ? 'text-amber-400' : 'text-gray-600' }} hover:text-amber-400 transition-colors">★</button>
                        @endfor
                    </form>
                @endauth
            </div>

            {{-- #32 Detailed Rating Criteria --}}
            @include('components.criteria-rating', ['file' => $file])

            {{-- Related --}}
            @if($related->isNotEmpty())
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">{{ __('messages.related_files') }}</h3>
                <div class="space-y-3">
                    @foreach($related as $rel)
                        <a href="{{ route('files.show', $rel) }}" class="flex items-center space-x-3 hover:bg-gray-700 rounded p-2 -mx-2 transition-colors">
                            <div class="w-16 h-10 bg-gray-700 rounded overflow-hidden flex-shrink-0">
                                @if($rel->thumbnail_url)
                                    <img src="{{ $rel->thumbnail_url }}" class="w-full h-full object-cover" loading="lazy" width="320" height="180">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm text-gray-200 truncate">{{ $rel->title }}</div>
                                <div class="text-xs text-gray-500">↓ {{ $rel->download_count }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
            {{-- Recently Viewed --}}
            @include('components.recently-viewed')
        </div>
    </div>
</div>

</x-layouts.app>