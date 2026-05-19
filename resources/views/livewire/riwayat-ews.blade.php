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
                    <tr class="hover:bg-gray-50">
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
                            <button type="button" wire:click="lihatDetail({{ $assessment->id }})" class="inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">
                                Lihat Detail
                            </button>
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

    <x-modal name="detail-rujukan" maxWidth="2xl">
        @if ($selectedAssessment)
            <div class="p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Detail Rujukan EWS</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $selectedAssessment->faskes->nama_faskes }} - {{ $selectedAssessment->waktu_penilaian->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <span @class([
                        'inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase',
                        'bg-red-100 text-red-700' => $selectedAssessment->zona === 'merah',
                        'bg-yellow-100 text-yellow-800' => $selectedAssessment->zona === 'kuning',
                        'bg-green-100 text-green-700' => $selectedAssessment->zona === 'hijau',
                    ])>{{ $selectedAssessment->zona }}</span>
                </div>

                <div class="mb-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Pasien</p>
                        <p class="mt-1 font-semibold text-gray-800">{{ $selectedAssessment->patient->nama_pasien }}</p>
                        <p class="text-xs text-gray-500">{{ $selectedAssessment->patient->no_rm }} / {{ $selectedAssessment->patient->jenis_kelamin }}</p>
                    </div>
                    <div class="rounded-lg border bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Skor EWS</p>
                        <p class="mt-1 text-2xl font-black text-gray-800">{{ $selectedAssessment->total_skor }}</p>
                        <p class="text-xs text-gray-500">{{ $selectedAssessment->zona_label }}</p>
                    </div>
                    <div class="rounded-lg border bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Status</p>
                        <p class="mt-1 font-semibold capitalize text-gray-800">{{ $selectedAssessment->status }}</p>
                        @if ($selectedAssessment->waktu_ditangani)
                            <p class="text-xs text-gray-500">Ditangani {{ $selectedAssessment->waktu_ditangani->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </div>

                <div class="mb-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Catatan Rujukan</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $selectedAssessment->catatan_rujukan ?: '-' }}</p>
                    </div>
                    <div class="rounded-lg border bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Tindakan yang Sudah Diberikan</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $selectedAssessment->tindakan_yang_diberikan ?: '-' }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <p class="text-xs font-semibold uppercase text-blue-700">Feedback Rumah Sakit</p>
                    @if ($selectedAssessment->feedback_hasil)
                        <p class="mt-2 font-semibold text-blue-900">{{ $selectedAssessment->feedback_label }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-blue-800">{{ $selectedAssessment->feedback_catatan ?: '-' }}</p>
                        <div class="mt-2 text-xs text-blue-700">
                            @if ($selectedAssessment->feedbackOleh)
                                Oleh {{ $selectedAssessment->feedbackOleh->name }}
                            @endif
                            @if ($selectedAssessment->waktu_feedback)
                                pada {{ $selectedAssessment->waktu_feedback->format('d/m/Y H:i') }}
                            @endif
                        </div>
                    @else
                        <p class="mt-2 text-sm text-blue-800">Belum ada feedback dari rumah sakit.</p>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" x-on:click="$dispatch('close-modal', 'detail-rujukan')" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                        Tutup
                    </button>
                </div>
            </div>
        @endif
    </x-modal>
</div>
