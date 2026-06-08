<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'EWS RSUD Depati Bahrin' }}</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Theme Store -->
    <script>
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
    </script>

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

    @livewireScripts
</body>

</html>
