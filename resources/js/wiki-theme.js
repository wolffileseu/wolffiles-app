// Wolffiles Wiki — Theme Toggle (light/dark) mit localStorage
// Inline-Init im <head> verhindert Flash-of-Wrong-Theme.

(function () {
    const KEY = 'wolffiles-wiki-theme';

    function apply(theme) {
        const root = document.querySelector('.wiki-skin');
        if (!root) return;
        if (theme === 'light') {
            root.classList.add('wiki-light');
        } else {
            root.classList.remove('wiki-light');
        }
        const btn = document.querySelector('.wiki-theme-toggle');
        if (btn) {
            btn.textContent = theme === 'light' ? '🌙 Dark Mode' : '☀️ Light Mode';
            btn.dataset.currentTheme = theme;
        }
    }

    function current() {
        return localStorage.getItem(KEY) || 'dark';
    }

    function toggle() {
        const next = current() === 'light' ? 'dark' : 'light';
        localStorage.setItem(KEY, next);
        apply(next);
    }

    // Mobile sidebar toggle (inkludiert hier weil eng verwandt)
    function mobileToggle() {
        const sb = document.querySelector('.wiki-sidebar');
        if (sb) sb.classList.toggle('is-open');
    }

    document.addEventListener('DOMContentLoaded', () => {
        apply(current());
        const btn = document.querySelector('.wiki-theme-toggle');
        if (btn) btn.addEventListener('click', toggle);
        const mb = document.querySelector('.wiki-mobile-toggle');
        if (mb) mb.addEventListener('click', mobileToggle);
    });

    // Globally für inline-onclick-Fallbacks
    window.wikiThemeToggle = toggle;
})();
