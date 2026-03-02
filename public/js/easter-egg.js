/**
 * Wolffiles.eu — Konami Code Easter Egg
 * ↑↑↓↓←→←→BA → Flicker → "ENEMY WEAKENED!" → Wolf3D → Konfetti → Badge
 *
 * Einbinden in app.blade.php:
 *   <script src="{{ asset('js/easter-egg.js') }}" defer></script>
 */

(function () {
    'use strict';

    // =========================================================================
    // CONFIG
    // =========================================================================

    const CONFIG = {
        // Konami Code Sequenz
        konamiSequence: [
            'ArrowUp', 'ArrowUp',
            'ArrowDown', 'ArrowDown',
            'ArrowLeft', 'ArrowRight',
            'ArrowLeft', 'ArrowRight',
            'KeyB', 'KeyA'
        ],

        // Timing (ms)
        flickerDuration: 800,
        enemyWeakenedDuration: 2500,
        confettiDuration: 5000,

        // Pfade
        wolf3dUrl: '/games/wolf3d/index.html',
        enemyWeakenedSound: '/sounds/enemy_weakened.mp3',
        badgeApiUrl: '/easter-egg/complete',

        // CSRF Token (Laravel)
        csrfToken: () => document.querySelector('meta[name="csrf-token"]')?.content || '',
    };

    // =========================================================================
    // KONAMI CODE LISTENER
    // =========================================================================

    let inputSequence = [];
    let sequenceTimeout = null;

    function initKonamiListener() {
        document.addEventListener('keydown', handleKeyDown);
    }

    function handleKeyDown(event) {
        // Ignoriere wenn User in einem Input-Feld tippt
        const tag = event.target.tagName.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
        if (event.target.isContentEditable) return;

        inputSequence.push(event.code);

        // Reset nach 3 Sekunden Inaktivität
        clearTimeout(sequenceTimeout);
        sequenceTimeout = setTimeout(() => { inputSequence = []; }, 3000);

        // Nur die letzten N Eingaben behalten
        if (inputSequence.length > CONFIG.konamiSequence.length) {
            inputSequence = inputSequence.slice(-CONFIG.konamiSequence.length);
        }

        // Prüfe ob Sequenz übereinstimmt
        if (inputSequence.length === CONFIG.konamiSequence.length) {
            const match = inputSequence.every((code, i) => code === CONFIG.konamiSequence[i]);
            if (match) {
                inputSequence = [];
                clearTimeout(sequenceTimeout);
                document.removeEventListener('keydown', handleKeyDown);
                activateEasterEgg();
            }
        }
    }

    // =========================================================================
    // EASTER EGG ACTIVATION — MAIN FLOW
    // =========================================================================

    async function activateEasterEgg() {
        try {
            // Schritt 1: Bildschirm-Flicker
            await showFlicker();

            // Schritt 2: "ENEMY WEAKENED!" Overlay + Sound
            await showEnemyWeakened();

            // Schritt 3: Wolf3D im Fullscreen-Overlay
            const levelCompleted = await launchWolf3D();

            // Schritt 4: Wenn Level geschafft → Konfetti + Badge
            if (levelCompleted) {
                showConfetti();
                await awardBadge();
            }
        } catch (err) {
            console.error('[Easter Egg] Error:', err);
        } finally {
            // Listener wieder aktivieren falls was schief geht
            initKonamiListener();
        }
    }

    // =========================================================================
    // SCHRITT 1: BILDSCHIRM-FLICKER
    // =========================================================================

    function showFlicker() {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'ee-flicker-overlay';
            document.body.appendChild(overlay);

            setTimeout(() => {
                overlay.remove();
                resolve();
            }, CONFIG.flickerDuration);
        });
    }

    // =========================================================================
    // SCHRITT 2: "ENEMY WEAKENED!" OVERLAY + SOUND
    // =========================================================================

    function showEnemyWeakened() {
        return new Promise((resolve) => {
            // Sound abspielen
            try {
                const audio = new Audio(CONFIG.enemyWeakenedSound);
                audio.volume = 0.8;
                audio.play().catch(() => {
                    // Audio autoplay blocked — kein Problem
                    console.warn('[Easter Egg] Audio autoplay blocked by browser');
                });
            } catch (e) {
                console.warn('[Easter Egg] Audio not available');
            }

            // Overlay erstellen
            const overlay = document.createElement('div');
            overlay.className = 'ee-announcement-overlay';
            overlay.innerHTML = `
                <div class="ee-announcement-content">
                    <div class="ee-announcement-icon">⚡</div>
                    <h1 class="ee-announcement-title">ENEMY WEAKENED!</h1>
                    <p class="ee-announcement-subtitle">Geheimwaffe aktiviert — Bereite dich vor, Soldat!</p>
                    <div class="ee-announcement-bar"></div>
                </div>
            `;
            document.body.appendChild(overlay);

            // Einblend-Animation
            requestAnimationFrame(() => {
                overlay.classList.add('ee-visible');
            });

            setTimeout(() => {
                overlay.classList.add('ee-fadeout');
                setTimeout(() => {
                    overlay.remove();
                    resolve();
                }, 500);
            }, CONFIG.enemyWeakenedDuration);
        });
    }

    // =========================================================================
    // SCHRITT 3: WOLF3D IM FULLSCREEN-OVERLAY
    // =========================================================================

    function launchWolf3D() {
        return new Promise((resolve) => {
            // Overlay Container
            const overlay = document.createElement('div');
            overlay.className = 'ee-wolf3d-overlay';
            overlay.innerHTML = `
                <div class="ee-wolf3d-header">
                    <div class="ee-wolf3d-header-left">
                        <span class="ee-wolf3d-logo">🐺</span>
                        <span class="ee-wolf3d-title">WOLFENSTEIN 3D — Wolffiles.eu Easter Egg</span>
                    </div>
                    <div class="ee-wolf3d-header-right">
                        <button class="ee-wolf3d-complete-btn" id="ee-wolf3d-complete">
                            🏆 Level geschafft!
                        </button>
                        <button class="ee-wolf3d-close-btn" id="ee-wolf3d-close">
                            ✕ Schließen
                        </button>
                    </div>
                </div>
                <div class="ee-wolf3d-frame-container">
                    <iframe
                        id="ee-wolf3d-iframe"
                        src="${CONFIG.wolf3dUrl}"
                        class="ee-wolf3d-iframe"
                        allowfullscreen
                        allow="autoplay"
                    ></iframe>
                    <div class="ee-wolf3d-loading" id="ee-wolf3d-loading">
                        <div class="ee-wolf3d-loading-spinner"></div>
                        <p>Lade Wolfenstein 3D...</p>
                    </div>
                </div>
                <div class="ee-wolf3d-footer">
                    <span>Steuerung: Pfeiltasten = Bewegen | X = Schießen | Leertaste = Tür öffnen | Shift = Rennen</span>
                </div>
            `;
            document.body.appendChild(overlay);

            // Einblenden
            requestAnimationFrame(() => {
                overlay.classList.add('ee-visible');
            });

            // Scroll verhindern
            document.body.style.overflow = 'hidden';

            // Loading-Spinner ausblenden wenn iframe geladen
            const iframe = document.getElementById('ee-wolf3d-iframe');
            const loading = document.getElementById('ee-wolf3d-loading');

            iframe.addEventListener('load', () => {
                if (loading) loading.style.display = 'none';
                iframe.focus();
            });

            // "Level geschafft!" Button
            document.getElementById('ee-wolf3d-complete').addEventListener('click', () => {
                cleanup();
                resolve(true);
            });

            // "Schließen" Button (ohne Badge)
            document.getElementById('ee-wolf3d-close').addEventListener('click', () => {
                cleanup();
                resolve(false);
            });

            // ESC zum Schließen (mit Bestätigung)
            function handleEsc(e) {
                if (e.key === 'Escape') {
                    // Doppeltes ESC als Sicherheit — erstes ESC geht an Wolf3D
                    if (document.activeElement === iframe) {
                        // Focus vom iframe nehmen
                        overlay.focus();
                    } else {
                        cleanup();
                        resolve(false);
                    }
                }
            }
            document.addEventListener('keydown', handleEsc);

            function cleanup() {
                document.removeEventListener('keydown', handleEsc);
                overlay.classList.add('ee-fadeout');
                document.body.style.overflow = '';
                setTimeout(() => overlay.remove(), 400);
            }
        });
    }

    // =========================================================================
    // SCHRITT 4: KONFETTI-EXPLOSION
    // =========================================================================

    function showConfetti() {
        // canvas-confetti inline laden falls nicht vorhanden
        if (typeof confetti === 'function') {
            fireConfetti();
            return;
        }

        // Dynamisch laden von lokalem Pfad oder CDN-Fallback
        const script = document.createElement('script');
        script.src = '/js/vendor/confetti.browser.min.js';
        script.onerror = () => {
            // Fallback: Eigene simple Konfetti-Animation
            fireSimpleConfetti();
        };
        script.onload = () => fireConfetti();
        document.head.appendChild(script);
    }

    function fireConfetti() {
        if (typeof confetti !== 'function') {
            fireSimpleConfetti();
            return;
        }

        // Erste Salve
        confetti({
            particleCount: 150,
            spread: 90,
            origin: { y: 0.6 },
            colors: ['#ff0000', '#ffffff', '#000000', '#ffd700', '#00ff00'],
        });

        // Zweite Salve nach kurzer Pause
        setTimeout(() => {
            confetti({
                particleCount: 100,
                angle: 60,
                spread: 70,
                origin: { x: 0 },
                colors: ['#ff0000', '#ffd700', '#ffffff'],
            });
            confetti({
                particleCount: 100,
                angle: 120,
                spread: 70,
                origin: { x: 1 },
                colors: ['#ff0000', '#ffd700', '#ffffff'],
            });
        }, 400);

        // Achievement Banner zeigen
        showAchievementBanner();
    }

    function fireSimpleConfetti() {
        // Fallback ohne canvas-confetti Library
        const container = document.createElement('div');
        container.className = 'ee-simple-confetti';
        const colors = ['#ff0000', '#ffffff', '#ffd700', '#00ff00', '#ff6600', '#0066ff'];

        for (let i = 0; i < 80; i++) {
            const piece = document.createElement('div');
            piece.className = 'ee-confetti-piece';
            piece.style.left = Math.random() * 100 + 'vw';
            piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            piece.style.animationDelay = Math.random() * 2 + 's';
            piece.style.animationDuration = (2 + Math.random() * 3) + 's';
            container.appendChild(piece);
        }

        document.body.appendChild(container);
        setTimeout(() => container.remove(), CONFIG.confettiDuration);

        showAchievementBanner();
    }

    function showAchievementBanner() {
        const banner = document.createElement('div');
        banner.className = 'ee-achievement-banner';
        banner.innerHTML = `
            <div class="ee-achievement-inner">
                <div class="ee-achievement-icon">🎮</div>
                <div class="ee-achievement-text">
                    <strong>ACHIEVEMENT UNLOCKED!</strong>
                    <span>Secret Agent — Wolffiles.eu</span>
                </div>
            </div>
        `;
        document.body.appendChild(banner);

        requestAnimationFrame(() => banner.classList.add('ee-visible'));
        setTimeout(() => {
            banner.classList.add('ee-fadeout');
            setTimeout(() => banner.remove(), 600);
        }, 4000);
    }

    // =========================================================================
    // SCHRITT 5: BADGE VERGEBEN (API CALL)
    // =========================================================================

    async function awardBadge() {
        try {
            const response = await fetch(CONFIG.badgeApiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CONFIG.csrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    _token: CONFIG.csrfToken(),
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                if (response.status === 401) {
                    console.info('[Easter Egg] User nicht eingeloggt — Badge wird nicht vergeben');
                } else if (response.status === 409) {
                    console.info('[Easter Egg] Badge bereits vorhanden');
                } else {
                    console.warn('[Easter Egg] Badge API Error:', errorData);
                }
                return;
            }

            const data = await response.json();
            console.info('[Easter Egg] Badge vergeben!', data);
        } catch (err) {
            console.warn('[Easter Egg] Badge API nicht erreichbar:', err);
        }
    }

    // =========================================================================
    // INIT
    // =========================================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKonamiListener);
    } else {
        initKonamiListener();
    }

})();
