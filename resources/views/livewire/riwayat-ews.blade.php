<div class="mx-auto max-w-6xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Riwayat Rujukan EWS</h1>
        <p class="text-sm text-gray-500">50 penilaian terakhir.</p>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative w-full sm:max-w-md">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            </span>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama pasien, no RM, atau faskes..."
                class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
            >
        </div>

        @if (trim($search) !== '')
            <button
                type="button"
                wire:click="resetPencarian"
                class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200"
            >
                Hapus Pencarian
            </button>
            <span class="text-sm text-gray-500">{{ $assessments->count() }} hasil ditemukan</span>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Pasien</th>
                    <th class="px-4 py-3">Faskes</th>
                    <th class="px-4 py-3">Skor</th>
                    <th class="px-4 py-3">Zona</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Feedback RS</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($assessments as $assessment)
                    <tr class="hover:bg-gray-50" wire:key="assessment-{{ $assessment->id }}">
                        <td class="px-4 py-3">{{ $assessment->waktu_penilaian->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $assessment->patient->nama_pasien }}</div>
                            <div class="text-xs text-gray-500">{{ $assessment->patient->no_rm }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $assessment->faskes->nama_faskes }}</td>
                        <td class="px-4 py-3 font-bold">{{ $assessment->total_skor }}</td>
                        <td class="px-4 py-3 uppercase">{{ $assessment->zona }}</td>
                        <td class="px-4 py-3 capitalize">{{ $assessment->status }}</td>
                        <td class="px-4 py-3">
                            @if ($assessment->feedback_hasil)
                                <div class="font-semibold text-gray-800">{{ $assessment->feedback_label }}</div>
                                @if ($assessment->waktu_feedback)
                                    <div class="text-xs text-gray-500">{{ $assessment->waktu_feedback->format('d/m/Y H:i') }}</div>
                                @endif
                            @else
                                <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">Belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ews.riwayat.detail', $assessment->id) }}" class="inline-flex rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                                Lihat Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                            @if (trim($search) !== '')
                                Tidak ada riwayat yang cocok dengan "{{ $search }}".
                            @else
                                Belum ada riwayat rujukan.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $assessments->links() }}
    </div>

</div>
