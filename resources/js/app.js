import './echo';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

(function () {
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

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        init() {
            const savedTheme = localStorage.getItem('theme');
            this.theme = savedTheme || 'light';
            this.updateTheme();
        },
        theme: 'light',
        toggle() {
            this.theme = this.theme === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', this.theme);
            this.updateTheme();
        },
        updateTheme() {
            const html = document.documentElement;
            const body = document.body;
            if (this.theme === 'dark') {
                html.classList.add('dark');
                body.classList.add('dark', 'bg-gray-900');
            } else {
                html.classList.remove('dark');
                body.classList.remove('dark', 'bg-gray-900');
            }
        }
    });

    Alpine.store('sidebar', {
        isExpanded: window.innerWidth >= 1280,
        isMobileOpen: false,
        isHovered: false,

        toggleExpanded() {
            this.isExpanded = !this.isExpanded;
            this.isMobileOpen = false;
        },

        toggleMobileOpen() {
            this.isMobileOpen = !this.isMobileOpen;
        },

        setMobileOpen(val) {
            this.isMobileOpen = val;
        },

        setHovered(val) {
            if (window.innerWidth >= 1280 && !this.isExpanded) {
                this.isHovered = val;
            }
        }
    });
});
