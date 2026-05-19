<div class="mx-auto max-w-7xl space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Rujukan belum ditangani dan alert real-time IGD RSUD Depati Bahrin.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($alarmAktif)
                <div class="animate-pulse rounded-full bg-red-100 px-4 py-2 text-red-700">
                    <span class="text-sm font-bold">{{ count($alertAktif) }} Alert Aktif</span>
                </div>
            @else
                <div class="rounded-full bg-green-100 px-4 py-2 text-green-700">
                    <span class="text-sm font-bold">Tidak Ada Alert</span>
                </div>
            @endif

            <button type="button" onclick="initAudio(); alert('Alarm diaktifkan.')" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">
                Aktifkan Alarm
            </button>
        </div>
    </div>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Rujukan Belum Ditangani</h2>
                <p class="text-sm text-gray-500">Data dari puskesmas atau rumah sakit perujuk yang masih menunggu penanganan.</p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-500">Belum Ditangani</p>
                <p class="mt-2 text-3xl font-black text-gray-800">{{ $totalMenunggu }}</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-red-700">Zona Merah</p>
                <p class="mt-2 text-3xl font-black text-red-700">{{ $totalMerah }}</p>
            </div>
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-yellow-700">Zona Kuning</p>
                <p class="mt-2 text-3xl font-black text-yellow-700">{{ $totalKuning }}</p>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-green-700">Zona Hijau</p>
                <p class="mt-2 text-3xl font-black text-green-700">{{ $totalHijau }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pasien</th>
                        <th class="px-4 py-3">Faskes Perujuk</th>
                        <th class="px-4 py-3 text-right">Skor</th>
                        <th class="px-4 py-3">Zona</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rujukanBelumDitangani as $rujukan)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $rujukan->waktu_penilaian->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $rujukan->patient->nama_pasien }}</div>
                                <div class="text-xs text-gray-500">{{ $rujukan->patient->no_rm }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $rujukan->faskes->nama_faskes }}</td>
                            <td class="px-4 py-3 text-right text-lg font-black">{{ $rujukan->total_skor }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-bold uppercase',
                                    'bg-red-100 text-red-700' => $rujukan->zona === 'merah',
                                    'bg-yellow-100 text-yellow-800' => $rujukan->zona === 'kuning',
                                    'bg-green-100 text-green-700' => $rujukan->zona === 'hijau',
                                ])>{{ $rujukan->zona }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $rujukan->petugas->name }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('rs.rujukan.detail', $rujukan) }}"
                                   class="inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">
                                    Tangani
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">Tidak ada rujukan yang belum ditangani.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-800">Alert Aktif IGD</h2>
            <p class="text-sm text-gray-500">Alert zona kuning dan merah yang dikirim real-time melalui Reverb.</p>
        </div>

        @forelse ($alertAktif as $alert)
            <div @class([
                'mb-4 overflow-hidden rounded-xl border-2 shadow-sm',
                'border-red-400 bg-red-50' => $alert['zona'] === 'merah',
                'border-yellow-400 bg-yellow-50' => $alert['zona'] !== 'merah',
            ])>
                <div @class([
                    'flex items-center justify-between px-6 py-3 text-white',
                    'bg-red-600' => $alert['zona'] === 'merah',
                    'bg-yellow-500' => $alert['zona'] !== 'merah',
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
                        'text-red-600 hover:bg-red-50' => $alert['zona'] === 'merah',
                        'text-yellow-700 hover:bg-yellow-50' => $alert['zona'] !== 'merah',
                    ])>
                        Tangani
                    </a>
                </div>

                <div class="px-6 py-4">
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div>
                            <p class="text-xs text-gray-500">Nama Pasien</p>
                            <p class="font-semibold text-gray-800">{{ $alert['nama_pasien'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">No. RM</p>
                            <p class="font-semibold text-gray-800">{{ $alert['no_rm'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Diinput oleh</p>
                            <p class="font-semibold text-gray-800">{{ $alert['petugas'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Asal Faskes</p>
                            <p class="font-semibold text-gray-800">{{ $alert['faskes_asal'] }}</p>
                        </div>
                    </div>

                    @if ($alert['catatan'])
                        <div class="mt-3 rounded-lg border bg-white p-3">
                            <p class="mb-1 text-xs text-gray-500">Catatan:</p>
                            <p class="text-sm text-gray-700">{{ $alert['catatan'] }}</p>
                        </div>
                    @endif

                    <div @class([
                        'mt-3 rounded-lg border p-3',
                        'border-red-200 bg-red-100 text-red-800' => $alert['zona'] === 'merah',
                        'border-yellow-200 bg-yellow-100 text-yellow-900' => $alert['zona'] !== 'merah',
                    ])>
                        <p class="mb-1 text-xs font-semibold uppercase">Respon Klinik:</p>
                        <p class="text-sm">{{ $alert['respon_klinik'] }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border bg-white py-16 text-center text-gray-400 shadow-sm">
                <p class="text-lg font-medium">Tidak ada alert aktif saat ini</p>
                <p class="text-sm">Semua pasien dalam kondisi terpantau</p>
            </div>
        @endforelse
    </section>
</div>
