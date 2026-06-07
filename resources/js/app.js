import './echo';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

(function() {
    const savedTheme = localStorage.getItem('theme');
    const theme = savedTheme || 'light';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
})();

// Don't start Alpine here if Livewire is managing it
// Livewire v3 already bundles and starts Alpine
// Alpine.start();
