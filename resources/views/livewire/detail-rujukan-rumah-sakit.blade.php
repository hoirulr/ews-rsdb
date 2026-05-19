<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Detail Rujukan EWS</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $assessment->faskes->nama_faskes }} - {{ $assessment->waktu_penilaian->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('igd.dashboard') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                Dashboard
            </a>
            <a href="{{ route('rs.daftar-rujukan') }}" class="rounded-lg bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-200">
                Daftar Rujukan
            </a>
        </div>
    </div>

    @if ($sukses)
        <div class="mb-4 rounded-lg border border-green-400 bg-green-100 p-4 text-green-800">{{ $pesanSukses }}</div>
    @endif

    @if ($pesanError)
        <div class="mb-4 rounded-lg border border-red-400 bg-red-100 p-4 text-red-800">{{ $pesanError }}</div>
    @endif

    <div @class([
        'mb-6 rounded-xl border-2 p-6 text-center',
        'border-red-400 bg-red-50' => $assessment->zona === 'merah',
        'border-yellow-400 bg-yellow-50' => $assessment->zona === 'kuning',
        'border-green-400 bg-green-50' => $assessment->zona === 'hijau',
    ])>
        <div @class([
            'text-6xl font-black',
            'text-red-600' => $assessment->zona === 'merah',
            'text-yellow-600' => $assessment->zona === 'kuning',
            'text-green-600' => $assessment->zona === 'hijau',
        ])>{{ $assessment->total_skor }}</div>
        <div @class([
            'mt-1 text-lg font-bold',
            'text-red-700' => $assessment->zona === 'merah',
            'text-yellow-700' => $assessment->zona === 'kuning',
            'text-green-700' => $assessment->zona === 'hijau',
        ])>{{ $assessment->zona_label }}</div>
        <p class="mx-auto mt-3 max-w-3xl text-sm text-gray-700">{{ $assessment->respon_klinik }}</p>
    </div>

    <div class="mb-6 rounded-xl border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">Data Pasien</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">Nama Pasien</p>
                <p class="font-semibold text-gray-800">{{ $assessment->patient->nama_pasien }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">No RM</p>
                <p class="font-semibold text-gray-800">{{ $assessment->patient->no_rm }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">Usia / JK</p>
                <p class="font-semibold text-gray-800">{{ $assessment->patient->umur }} tahun / {{ $assessment->patient->jenis_kelamin }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">Tanggal Lahir</p>
                <p class="font-semibold text-gray-800">{{ $assessment->patient->tanggal_lahir->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">Faskes Perujuk</p>
                <p class="font-semibold text-gray-800">{{ $assessment->faskes->nama_faskes }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">Petugas Input</p>
                <p class="font-semibold text-gray-800">{{ $assessment->petugas->name }}</p>
            </div>
        </div>
    </div>

    <div class="mb-6 rounded-xl border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">Vital Sign</h2>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ([
                ['Respirasi', $assessment->respirasi.' /min', $assessment->skor_respirasi],
                ['SpO2', $assessment->saturasi_o2.'%', $assessment->skor_saturasi],
                ['O2 Tambahan', $assessment->oksigen_tambahan ? 'Ya' : 'Tidak', $assessment->skor_oksigen],
                ['Suhu', $assessment->suhu.' C', $assessment->skor_suhu],
                ['TD Sistolik', $assessment->td_sistolik.' mmHg', $assessment->skor_td],
                ['Nadi', $assessment->nadi.' bpm', $assessment->skor_nadi],
                ['Kesadaran', $assessment->kesadaran, $assessment->skor_kesadaran],
            ] as [$label, $value, $score])
                <div class="rounded-lg border bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-lg font-bold text-gray-800">{{ $value }}</p>
                    <span @class([
                        'mt-2 inline-flex rounded px-2 py-1 text-xs font-bold',
                        'bg-red-100 text-red-700' => $score >= 3,
                        'bg-orange-100 text-orange-700' => $score === 2,
                        'bg-yellow-100 text-yellow-800' => $score === 1,
                        'bg-green-100 text-green-700' => $score === 0,
                    ])>Skor: {{ $score }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-6 rounded-xl border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">Catatan Rujukan</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Riwayat Singkat & Keluhan</p>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $assessment->catatan_rujukan ?: '-' }}</p>
            </div>
            <div class="rounded-lg border bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Tindakan yang Sudah Diberikan</p>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $assessment->tindakan_yang_diberikan ?: '-' }}</p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="simpanFeedback" class="rounded-xl border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">Feedback dan Catatan Rumah Sakit</h2>

        @if ($assessment->feedback_hasil)
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Feedback terakhir: <strong>{{ $assessment->feedback_label }}</strong>
                @if ($assessment->waktu_feedback)
                    pada {{ $assessment->waktu_feedback->format('d/m/Y H:i') }}
                @endif
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Hasil Feedback *</label>
                <select wire:model="feedbackHasil" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih feedback</option>
                    <option value="meninggal">Meninggal</option>
                    <option value="icu_lebih_24_jam">Rawat Lebih dari 24 Jam di ICU</option>
                    <option value="rawat_inap_lebih_24_jam">Rawat lebih dari 24 Jam di ruangan rawat inap</option>
                </select>
                @error('feedbackHasil') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Catatan Rumah Sakit</label>
                <textarea wire:model="feedbackCatatan" rows="3" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Catatan tambahan dari rumah sakit..."></textarea>
                @error('feedbackCatatan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
