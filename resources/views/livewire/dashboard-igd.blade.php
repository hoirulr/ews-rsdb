<div class="space-y-6" wire:poll.10s>
    @if (session()->has('sukses'))
        <div class="rounded-xl border border-success-200 bg-success-50 p-4 text-success-800 dark:border-success-800 dark:bg-success-500/10 dark:text-success-300">
            {{ session('sukses') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rujukan belum ditangani dan alert real-time IGD RSUD Depati Bahrin.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($alarmAktif)
                <div class="animate-pulse rounded-full bg-error-100 px-4 py-2 text-error-700 dark:bg-error-500/20 dark:text-error-400">
                    <span class="text-sm font-bold">{{ count($alertAktif) }} Alert Aktif</span>
                </div>
            @else
                <div class="rounded-full bg-success-100 px-4 py-2 text-success-700 dark:bg-success-500/20 dark:text-success-400">
                    <span class="text-sm font-bold">Tidak Ada Alert</span>
                </div>
            @endif

        </div>
    </div>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Rujukan Belum Ditangani</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Data dari puskesmas atau rumah sakit perujuk yang masih menunggu penanganan.</p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Belum Ditangani</p>
                <p class="mt-2 text-3xl font-black text-gray-800 dark:text-white/90">{{ $totalMenunggu }}</p>
            </div>
            <div class="rounded-xl border border-error-200 bg-error-50 p-5 shadow-theme-xs dark:border-error-800 dark:bg-error-500/10">
                <p class="text-xs font-semibold uppercase text-error-700 dark:text-error-400">Zona Merah</p>
                <p class="mt-2 text-3xl font-black text-error-700 dark:text-error-400">{{ $totalMerah }}</p>
            </div>
            <div class="rounded-xl border border-warning-200 bg-warning-50 p-5 shadow-theme-xs dark:border-warning-800 dark:bg-warning-500/10">
                <p class="text-xs font-semibold uppercase text-warning-700 dark:text-warning-400">Zona Kuning</p>
                <p class="mt-2 text-3xl font-black text-warning-700 dark:text-warning-400">{{ $totalKuning }}</p>
            </div>
            <div class="rounded-xl border border-success-200 bg-success-50 p-5 shadow-theme-xs dark:border-success-800 dark:bg-success-500/10">
                <p class="text-xs font-semibold uppercase text-success-700 dark:text-success-400">Zona Hijau</p>
                <p class="mt-2 text-3xl font-black text-success-700 dark:text-success-400">{{ $totalHijau }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Pasien</th>
                            <th class="px-5 py-3">Faskes Perujuk</th>
                            <th class="px-5 py-3 text-right">Skor</th>
                            <th class="px-5 py-3">Zona</th>
                            <th class="px-5 py-3">Petugas</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($rujukanBelumDitangani as $rujukan)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $rujukan->waktu_penilaian->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-gray-800 dark:text-white/90">{{ $rujukan->patient->nama_pasien }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $rujukan->patient->no_rm }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $rujukan->faskes->nama_faskes }}</td>
                                <td class="px-5 py-4 text-right text-lg font-black text-gray-800 dark:text-white/90">{{ $rujukan->total_skor }}</td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'rounded-full px-3 py-1 text-xs font-bold uppercase',
                                        'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-400' => $rujukan->zona === 'merah',
                                        'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $rujukan->zona === 'kuning',
                                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $rujukan->zona === 'hijau',
                                    ])>{{ $rujukan->zona }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $rujukan->petugas->name }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('rs.rujukan.detail', $rujukan) }}"
                                       class="inline-flex rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                                        Tangani
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">Tidak ada rujukan yang belum ditangani.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Alert Aktif IGD</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Alert zona kuning dan merah yang dikirim real-time melalui Reverb.</p>
        </div>

        @forelse ($alertAktif as $alert)
            <div @class([
                'mb-4 overflow-hidden rounded-xl border-2 shadow-theme-sm',
                'border-error-400 bg-error-50 dark:border-error-700 dark:bg-error-500/10' => $alert['zona'] === 'merah',
                'border-warning-400 bg-warning-50 dark:border-warning-700 dark:bg-warning-500/10' => $alert['zona'] !== 'merah',
            ])>
                <div @class([
                    'flex items-center justify-between px-6 py-3 text-white',
                    'bg-error-600 dark:bg-error-700' => $alert['zona'] === 'merah',
                    'bg-warning-500 dark:bg-warning-600' => $alert['zona'] !== 'merah',
                ])>
                    <div class="flex items-center gap-3">
                        <div class="text-3xl font-black">{{ $alert['total_skor'] }}</div>
                        <div>
                            <div class="text-lg font-bold">{{ $alert['zona_label'] }}</div>
                            <div class="text-sm opacity-90">{{ $alert['faskes_asal'] }} - {{ $alert['waktu'] }}</div>
                        </div>
                    </div>
                    <a href="{{ route('rs.rujukan.detail', $alert['id']) }}" @class([
                        'rounded-lg bg-white px-5 py-2 font-bold transition',
                        'text-error-600 hover:bg-error-50' => $alert['zona'] === 'merah',
                        'text-warning-700 hover:bg-warning-50' => $alert['zona'] !== 'merah',
                    ])>
                        Tangani
                    </a>
                </div>

                <div class="px-6 py-4">
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Nama Pasien</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $alert['nama_pasien'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">No. RM</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $alert['no_rm'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Diinput oleh</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $alert['petugas'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Asal Faskes</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $alert['faskes_asal'] }}</p>
                        </div>
                    </div>

                    @if ($alert['catatan'])
                        <div class="mt-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-white/[0.03]">
                            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Catatan:</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $alert['catatan'] }}</p>
                        </div>
                    @endif

                    <div @class([
                        'mt-3 rounded-lg border p-3',
                        'border-error-200 bg-error-100 text-error-800 dark:border-error-700 dark:bg-error-500/20 dark:text-error-300' => $alert['zona'] === 'merah',
                        'border-warning-200 bg-warning-100 text-warning-900 dark:border-warning-700 dark:bg-warning-500/20 dark:text-warning-300' => $alert['zona'] !== 'merah',
                    ])>
                        <p class="mb-1 text-xs font-semibold uppercase">Respon Klinik:</p>
                        <p class="text-sm">{{ $alert['respon_klinik'] }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white py-16 text-center shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-lg font-medium text-gray-400 dark:text-gray-500">Tidak ada alert aktif saat ini</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">Semua pasien dalam kondisi terpantau</p>
            </div>
        @endforelse
    </section>
</div>
