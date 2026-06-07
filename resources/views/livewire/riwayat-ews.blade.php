<div class="mx-auto max-w-6xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Riwayat Rujukan EWS</h1>
        <p class="text-sm text-gray-500">50 penilaian terakhir.</p>
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
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500">Belum ada riwayat rujukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
