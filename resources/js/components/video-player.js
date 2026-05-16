import Plyr from 'plyr';

/**
 * Alpine.js component for the Wolffiles video player.
 *
 * Usage in Blade:
 *   <div x-data="videoPlayer()" x-init="init($refs.video)">
 *     <video x-ref="video" playsinline controls
 *            poster="{{ $posterUrl }}"
 *            data-poster="{{ $posterUrl }}">
 *       <source src="{{ route('files.stream', $file->slug) }}" type="video/mp4">
 *     </video>
 *   </div>
 */
export default function videoPlayer() {
    return {
        player: null,
        init(videoEl) {
            if (!videoEl) return;
            this.player = new Plyr(videoEl, {
                controls: [
                    'play-large',
                    'play',
                    'progress',
                    'current-time',
                    'duration',
                    'mute',
                    'volume',
                    'captions',
                    'settings',
                    'pip',
                    'airplay',
                    'fullscreen',
                ],
                settings: ['quality', 'speed'],
                speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
                keyboard: { focused: true, global: false },
                tooltips: { controls: true, seek: true },
                fullscreen: { enabled: true, fallback: true, iosNative: true },
                ratio: '16:9',
                storage: { enabled: true, key: 'wolffiles-plyr' },
            });

            // Auto-pause if another video on the page starts (future-proofing)
            this.player.on('play', () => {
                document.querySelectorAll('video').forEach((v) => {
                    if (v !== videoEl && !v.paused) v.pause();
                });
            });
        },
        destroy() {
            if (this.player) {
                this.player.destroy();
                this.player = null;
            }
        },
    };
}
