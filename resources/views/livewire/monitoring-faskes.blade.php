<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Monitoring Faskes</h1>
        <p class="text-sm text-gray-500">Ringkasan faskes perujuk dan jumlah penilaian EWS.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($faskesList as $faskes)
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-bold text-gray-800">{{ $faskes->nama_faskes }}</h2>
                        <p class="text-sm text-gray-500">{{ $faskes->kode_faskes }} - {{ $faskes->tipe_label }}</p>
                    </div>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">{{ $faskes->ews_assessments_count }} EWS</span>
                </div>
                <p class="mt-3 text-sm text-gray-600">{{ $faskes->alamat ?: 'Alamat belum diisi' }}</p>
            </div>
        @endforeach
    </div>
</div>
