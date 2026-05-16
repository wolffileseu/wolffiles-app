import Plyr from 'plyr';

/**
 * Alpine.js video player component with YouTube-style scrub previews.
 *
 * @param videoEl   The <video> DOM element
 * @param scrubVttUrl  Optional URL to a WebVTT thumbnail track
 */
export default function videoPlayer() {
    return {
        player: null,
        init(videoEl, scrubVttUrl = null) {
            if (!videoEl) return;

            const opts = {
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
            };

            // Enable YouTube-style scrub thumbnails if we have a VTT
            if (scrubVttUrl) {
                opts.previewThumbnails = {
                    enabled: true,
                    src: scrubVttUrl,
                };
            }

            this.player = new Plyr(videoEl, opts);

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
