{{-- Dark/Light Mode Toggle --}}
{{-- Include in navbar: @include('components.theme-toggle') --}}
<div x-data="{
    darkMode: localStorage.getItem('theme') !== 'light',
    toggle() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.darkMode);
        document.documentElement.classList.toggle('light', !this.darkMode);
    }
}" x-init="document.documentElement.classList.toggle('dark', darkMode); document.documentElement.classList.toggle('light', !darkMode)">
    <button @click="toggle()"
            class="p-2 rounded-lg text-gray-400 hover:text-white transition-colors"
            :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
        {{-- Sun icon (shown in dark mode) --}}
        <svg x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        {{-- Moon icon (shown in light mode) --}}
        <svg x-show="!darkMode" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
    </button>
</div>
