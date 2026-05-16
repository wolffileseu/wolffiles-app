import './bootstrap';
import Alpine from 'alpinejs';
import videoPlayer from './components/video-player';

window.Alpine = Alpine;

// Register Alpine components
Alpine.data('videoPlayer', videoPlayer);

Alpine.start();
