<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rekap Rujukan IGD</h1>
            <p class="mt-1 text-sm text-gray-500">Jumlah rujukan per puskesmas atau rumah sakit perujuk berdasarkan zona EWS.</p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-wrap items-end gap-3 rounded-lg border bg-white p-4 shadow-sm">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Dari</label>
                    <input type="date" wire:model.live="tanggalMulai" class="rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Sampai</label>
                    <input type="date" wire:model.live="tanggalSelesai" class="rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="button" wire:click="resetFilter" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                    Reset
                </button>
            <a href="{{ $exportUrl }}"
               class="rounded-lg border border-green-700 px-4 py-2 text-sm font-semibold shadow-sm transition hover:opacity-90"
               style="background-color: #15803d; color: #ffffff;">
                Export Excel
            </a>
            </div>

            
        </div>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-gray-500">Total Rujukan</p>
            <p class="mt-2 text-3xl font-black text-gray-800">{{ $totalRujukan }}</p>
        </div>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-green-700">Zona Hijau</p>
            <p class="mt-2 text-3xl font-black text-green-700">{{ $totalHijau }}</p>
        </div>
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-yellow-700">Zona Kuning</p>
            <p class="mt-2 text-3xl font-black text-yellow-700">{{ $totalKuning }}</p>
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-red-700">Zona Merah</p>
            <p class="mt-2 text-3xl font-black text-red-700">{{ $totalMerah }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Faskes Perujuk</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3 text-right">Hijau</th>
                    <th class="px-4 py-3 text-right">Kuning</th>
                    <th class="px-4 py-3 text-right">Merah</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rekap as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $item->nama_faskes }}</div>
                            <div class="text-xs text-gray-500">{{ $item->kode_faskes }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $item->tipe_label }}</td>
                        <td class="px-4 py-3 text-right font-bold text-green-700">{{ $item->total_hijau }}</td>
                        <td class="px-4 py-3 text-right font-bold text-yellow-700">{{ $item->total_kuning }}</td>
                        <td class="px-4 py-3 text-right font-bold text-red-700">{{ $item->total_merah }}</td>
                        <td class="px-4 py-3 text-right font-black text-gray-800">{{ $item->total_rujukan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">Belum ada data faskes perujuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
