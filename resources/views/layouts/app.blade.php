<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'EWS RSUD Depati Bahrin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-600">
                        <span class="text-lg font-black">E</span>
                    </div>
                    <div>
                        <span class="text-lg font-bold">EWS RSUD Depati Bahrin</span>
                        <p class="text-xs text-blue-300">Early Warning Score System</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-blue-200">{{ auth()->user()->name }}</span>
                    <span class="rounded-full bg-blue-700 px-2 py-1 text-xs">{{ auth()->user()->getRoleNames()->first() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-blue-300 transition hover:text-white">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex h-[calc(100vh-4rem)]">
        <aside class="w-64 flex-shrink-0 overflow-y-auto bg-blue-800 text-white">
            <nav class="space-y-1 p-4">
                @role('puskesmas|rs_perujuk|admin_sistem')
                    <a href="{{ route('ews.form') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('ews.form'),
                    ])>
                        <span>+</span>
                        <span>Input Rujukan EWS</span>
                    </a>
                    <a href="{{ route('ews.riwayat') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('ews.riwayat'),
                    ])>
                        <span>R</span>
                        <span>Riwayat Rujukan</span>
                    </a>
                @endrole

                @role('admin_rsud|admin_sistem')
                    <div class="pb-1 pt-4">
                        <p class="px-4 text-xs font-semibold uppercase text-blue-400">IGD RSUD</p>
                    </div>
                    <a href="{{ route('igd.dashboard') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('igd.dashboard') || request()->routeIs('rs.dashboard'),
                    ])>
                        <span>D</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('rs.daftar-rujukan') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('rs.daftar-rujukan') || request()->routeIs('rs.rujukan.detail'),
                    ])>
                        <span>L</span>
                        <span>Daftar Rujukan</span>
                    </a>
                    <a href="{{ route('igd.monitoring') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('igd.monitoring'),
                    ])>
                        <span>M</span>
                        <span>Monitoring Faskes</span>
                    </a>
                    <a href="{{ route('igd.rekap-rujukan') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('igd.rekap-rujukan'),
                    ])>
                        <span>K</span>
                        <span>Rekap Rujukan</span>
                    </a>
                @endrole

                @role('admin_sistem')
                    <div class="pb-1 pt-4">
                        <p class="px-4 text-xs font-semibold uppercase text-blue-400">Admin Sistem</p>
                    </div>
                    <a href="{{ route('admin.user') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('admin.user'),
                    ])>
                        <span>U</span>
                        <span>Manajemen User</span>
                    </a>
                    <a href="{{ route('admin.faskes') }}" @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition hover:bg-blue-700',
                        'bg-blue-700' => request()->routeIs('admin.faskes'),
                    ])>
                        <span>F</span>
                        <span>Manajemen Faskes</span>
                    </a>
                @endrole
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>

    <script>
        window.audioCtx = null;
        window.alarmInterval = null;
        window.alarmTimeout = null;

        window.initAudio = function () {
            if (!window.audioCtx) {
                window.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
        };

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
</body>
</html>
