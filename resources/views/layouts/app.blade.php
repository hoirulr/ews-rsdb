<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'EWS RSUD Depati Bahrin' }}</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])


    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const theme = savedTheme || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                // document.body.classList.add('dark', 'bg-gray-900');
            } else {
                document.documentElement.classList.remove('dark');
                // document.body.classList.remove('dark', 'bg-gray-900');
            }
        })();
    </script>

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    @livewireStyles
</head>

<body
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                {{ $slot }}
            </div>
        </div>

    </div>

    <!-- Alarm Audio Scripts (EWS-specific) -->
    <script>
        window.audioCtx = null;
        window.alarmInterval = null;
        window.alarmTimeout = null;

        window.initAudio = function () {
            if (!window.audioCtx) {
                window.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (window.audioCtx.state === 'suspended') {
                window.audioCtx.resume();
            }
        };

        const unlockAudio = () => {
            window.initAudio();
            document.removeEventListener('click', unlockAudio);
            document.removeEventListener('keydown', unlockAudio);
            document.removeEventListener('touchstart', unlockAudio);
        };
        document.addEventListener('click', unlockAudio, { once: true });
        document.addEventListener('keydown', unlockAudio, { once: true });
        document.addEventListener('touchstart', unlockAudio, { once: true });

        function stopAlarm() {
            if (window.alarmInterval) {
                clearInterval(window.alarmInterval);
            }

            if (window.alarmTimeout) {
                clearTimeout(window.alarmTimeout);
            }

            window.alarmInterval = null;
            window.alarmTimeout = null;
        }

        function bunyikanAlarm(zona) {
            window.initAudio();

            if (!window.audioCtx) {
                return;
            }

            const freq = zona === 'merah' ? 800 : 500;
            stopAlarm();

            function beep() {
                const osc = window.audioCtx.createOscillator();
                const gain = window.audioCtx.createGain();
                osc.connect(gain);
                gain.connect(window.audioCtx.destination);
                osc.frequency.value = freq;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.5, window.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, window.audioCtx.currentTime + 0.5);
                osc.start(window.audioCtx.currentTime);
                osc.stop(window.audioCtx.currentTime + 0.5);
            }

            beep();
            window.alarmInterval = setInterval(beep, 1000);
            window.alarmTimeout = setTimeout(stopAlarm, 30000);
        }

        document.addEventListener('livewire:init', () => {
            Livewire.on('bunyikan-alarm', ({ zona }) => bunyikanAlarm(zona));
            Livewire.on('hentikan-alarm', () => stopAlarm());
        });
    </script>

    <!-- Keep-Alive: Prevent session from expiring on standby apps -->
    <script>
        (function () {
            const KEEP_ALIVE_INTERVAL = 10 * 60 * 1000; // 10 minutes
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            setInterval(function () {
                fetch('{{ route("keep-alive") }}', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                }).catch(function () {
                    // Session likely expired, reload to trigger login redirect
                    window.location.reload();
                });
            }, KEEP_ALIVE_INTERVAL);
        })();
    </script>

    <!-- Global IGD Alert Notification via Echo (all IGD pages) -->
    @auth
        @role('admin_rsud|admin_sistem')
        <div id="igd-alert-toast-container" class="fixed right-4 top-4 z-[99999] flex flex-col gap-3" style="pointer-events: none;"></div>

        <template id="igd-alert-toast-template">
            <div class="igd-alert-toast animate-slide-in pointer-events-auto w-96 overflow-hidden rounded-xl border-2 shadow-2xl transition-all duration-500"
                 style="opacity: 1;">
                <div class="flex items-center justify-between px-4 py-3 text-white">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold toast-title">Rujukan Masuk</div>
                            <div class="text-xs opacity-90 toast-subtitle"></div>
                        </div>
                    </div>
                    <button onclick="this.closest('.igd-alert-toast').remove()" class="ml-2 rounded-lg p-1 transition hover:bg-white/20">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="bg-white px-4 py-3 dark:bg-gray-900">
                    <div class="mb-2 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-xs text-gray-500">Pasien</span>
                            <p class="font-semibold text-gray-800 dark:text-white/90 toast-pasien"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Skor EWS</span>
                            <p class="font-black text-gray-800 dark:text-white/90 toast-skor"></p>
                        </div>
                    </div>
                    <a href="#" class="toast-link mt-2 block w-full rounded-lg px-4 py-2 text-center text-sm font-semibold text-white transition">
                        Tangani Sekarang
                    </a>
                </div>
            </div>
        </template>

        <style>
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .animate-slide-in { animation: slideIn 0.4s ease-out; }
            .animate-slide-out { animation: slideOut 0.4s ease-in forwards; }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.Echo === 'undefined') {
                    return;
                }

                window.Echo.private('ews-alerts.rsud')
                    .listen('.ews.alert', function (data) {
                        // Play alarm sound
                        bunyikanAlarm(data.zona || 'kuning');

                        // Show toast notification
                        showIgdAlertToast(data);

                        // Also notify Livewire components if they exist on this page
                        if (typeof Livewire !== 'undefined') {
                            Livewire.dispatch('rujukan-baru-masuk', { data: data });
                        }
                    });
            });

            function showIgdAlertToast(data) {
                const container = document.getElementById('igd-alert-toast-container');
                const template = document.getElementById('igd-alert-toast-template');
                if (!container || !template) return;

                const clone = template.content.cloneNode(true);
                const toast = clone.querySelector('.igd-alert-toast');

                const isMerah = data.zona === 'merah';
                const headerDiv = toast.querySelector('.flex.items-center.justify-between');
                const link = toast.querySelector('.toast-link');

                if (isMerah) {
                    toast.classList.add('border-red-400');
                    headerDiv.classList.add('bg-red-600');
                    link.classList.add('bg-red-600', 'hover:bg-red-700');
                } else {
                    toast.classList.add('border-yellow-400');
                    headerDiv.classList.add('bg-yellow-500');
                    link.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
                }

                const zonaLabel = isMerah ? 'ZONA MERAH - Gawat Darurat' : 'ZONA KUNING - Waspada';
                toast.querySelector('.toast-title').textContent = zonaLabel;
                toast.querySelector('.toast-subtitle').textContent = (data.faskes_asal || '') + ' - ' + (data.waktu || '');
                toast.querySelector('.toast-pasien').textContent = data.nama_pasien || '-';
                toast.querySelector('.toast-skor').textContent = data.total_skor || '0';
                link.href = '/rs/rujukan/' + (data.id || '');

                container.appendChild(clone);

                // Auto-dismiss after 30 seconds
                setTimeout(function () {
                    const existing = container.querySelector('.igd-alert-toast:first-child');
                    if (existing) {
                        existing.classList.add('animate-slide-out');
                        setTimeout(() => existing.remove(), 400);
                    }
                }, 30000);

                // Keep max 5 toasts visible
                const toasts = container.querySelectorAll('.igd-alert-toast');
                if (toasts.length > 5) {
                    toasts[0].remove();
                }
            }
        </script>
        @endrole
    @endauth

    @livewireScripts
</body>

</html>

