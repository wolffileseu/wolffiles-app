{{-- Click Heatmap Tracker --}}
@if(!request()->is('admin*'))
<script>
(function() {
    let clicks = [];
    let lastSent = Date.now();

    document.addEventListener('click', function(e) {
        clicks.push({
            x: Math.round((e.pageX / document.documentElement.scrollWidth) * 10000) / 100,
            y: Math.round(e.pageY),
            el: e.target.tagName.toLowerCase() + (e.target.className ? '.' + String(e.target.className).split(' ')[0] : ''),
            path: window.location.pathname,
            w: window.innerWidth,
            t: Date.now()
        });

        if (clicks.length >= 10 || (Date.now() - lastSent) > 30000) {
            sendClicks();
        }
    });

    window.addEventListener('beforeunload', sendClicks);

    function sendClicks() {
        if (clicks.length === 0) return;
        const data = clicks.splice(0);
        lastSent = Date.now();

        const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
        navigator.sendBeacon('/api/heatmap', blob);
    }
})();
</script>
@endif
