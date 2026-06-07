<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Rujukan</h1>
            <p class="mt-1 text-sm text-gray-500">Seluruh pasien rujukan yang sudah ditangani rumah sakit.</p>
        </div>

        <div class="flex flex-wrap items-end gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Cari</label>
                <input type="search" wire:model.live.debounce.400ms="search" class="w-56 rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Pasien, RM, faskes">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Status</label>
                <select wire:model.live="status" class="rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="semua">Semua</option>
                    <option value="ditangani">Ditangani</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Zona</label>
                <select wire:model.live="zona" class="rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="semua">Semua</option>
                    <option value="hijau">Hijau</option>
                    <option value="kuning">Kuning</option>
                    <option value="merah">Merah</option>
                </select>
            </div>
            <button type="button" wire:click="resetFilter" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                Reset
            </button>
        </div>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-gray-500">Total Rujukan</p>
            <p class="mt-2 text-3xl font-black text-gray-800">{{ $totalRujukan }}</p>
        </div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-blue-700">Ditangani</p>
            <p class="mt-2 text-3xl font-black text-blue-700">{{ $totalDitangani }}</p>
        </div>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-green-700">Selesai</p>
            <p class="mt-2 text-3xl font-black text-green-700">{{ $totalSelesai }}</p>
        </div>
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-yellow-700">Belum Feedback</p>
            <p class="mt-2 text-3xl font-black text-yellow-700">{{ $totalBelumFeedback }}</p>
        </div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2" wire:ignore>
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-800">Rujukan per Zona (Bulan Ini)</h2>
            <div class="relative h-64 w-full">
                <canvas id="chartZona"></canvas>
            </div>
        </div>
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-800">Rujukan per Faskes (Bulan Ini)</h2>
            <div class="relative h-64 w-full">
                <canvas id="chartFaskes"></canvas>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Waktu Rujukan</th>
                    <th class="px-4 py-3">Faskes Perujuk</th>
                    <th class="px-4 py-3">Pasien</th>
                    <th class="px-4 py-3 text-right">Skor EWS</th>
                    <th class="px-4 py-3">Zona</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Feedback</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rujukanDitangani as $rujukan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div>{{ $rujukan->waktu_penilaian->format('d/m/Y H:i') }}</div>
                            @if ($rujukan->waktu_ditangani)
                                <div class="text-xs text-gray-500">Ditangani {{ $rujukan->waktu_ditangani->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $rujukan->faskes->nama_faskes }}</div>
                            <div class="text-xs text-gray-500">{{ $rujukan->faskes->tipe_label }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $rujukan->patient->nama_pasien }}</div>
                            <div class="text-xs text-gray-500">{{ $rujukan->patient->no_rm }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-lg font-black">{{ $rujukan->total_skor }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-bold uppercase',
                                'bg-red-100 text-red-700' => $rujukan->zona === 'merah',
                                'bg-yellow-100 text-yellow-800' => $rujukan->zona === 'kuning',
                                'bg-green-100 text-green-700' => $rujukan->zona === 'hijau',
                            ])>{{ $rujukan->zona }}</span>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $rujukan->status }}</td>
                        <td class="px-4 py-3">
                            @if ($rujukan->feedback_hasil)
                                <div class="font-semibold text-gray-800">{{ $rujukan->feedback_label }}</div>
                                @if ($rujukan->waktu_feedback)
                                    <div class="text-xs text-gray-500">{{ $rujukan->waktu_feedback->format('d/m/Y H:i') }}</div>
                                @endif
                            @else
                                <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">Belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('rs.rujukan.detail', $rujukan) }}"
                               class="inline-flex rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                                Lihat Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">Belum ada rujukan yang sudah ditangani.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endassets

    @script
    <script>
        const chartZonaData = @json(array_values($rujukanPerZona));
        const chartFaskesLabels = @json(array_keys($rujukanPerFaskes));
        const chartFaskesData = @json(array_values($rujukanPerFaskes));

        new Chart(document.getElementById('chartZona'), {
            type: 'doughnut',
            data: {
                labels: ['Hijau', 'Kuning', 'Merah'],
                datasets: [{
                    data: chartZonaData,
                    backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        new Chart(document.getElementById('chartFaskes'), {
            type: 'bar',
            data: {
                labels: chartFaskesLabels,
                datasets: [{
                    label: 'Jumlah Rujukan',
                    data: chartFaskesData,
                    backgroundColor: '#3b82f6',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    @endscript
</div>
