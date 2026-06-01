<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Form Rujukan EWS</h1>
        <p class="mt-1 text-sm text-gray-500">{{ auth()->user()->faskes->nama_faskes ?? '' }}</p>
    </div>

    @if ($sukses)
        <div @class([
            'mb-4 rounded-lg border p-4',
            'border-red-400 bg-red-100 text-red-800' => $zona === 'merah',
            'border-yellow-400 bg-yellow-100 text-yellow-800' => $zona === 'kuning',
            'border-green-400 bg-green-100 text-green-800' => $zona === 'hijau' || $zona === '',
        ])>
            {{ $pesanSukses }}
        </div>
    @endif

    @if ($pesanError)
        <div class="mb-4 rounded-lg border border-red-400 bg-red-100 p-4 text-red-800">
            {{ $pesanError }}
        </div>
    @endif

    @if ($zona)
        <div @class([
            'mb-6 rounded-xl border-2 p-6 text-center',
            'border-red-400 bg-red-50' => $zona === 'merah',
            'border-yellow-400 bg-yellow-50' => $zona === 'kuning',
            'border-green-400 bg-green-50' => $zona === 'hijau',
        ])>
            <div @class([
                'text-6xl font-black',
                'text-red-600' => $zona === 'merah',
                'text-yellow-600' => $zona === 'kuning',
                'text-green-600' => $zona === 'hijau',
            ])>{{ $total_skor }}</div>
            <div @class([
                'mt-1 text-lg font-bold',
                'text-red-700' => $zona === 'merah',
                'text-yellow-700' => $zona === 'kuning',
                'text-green-700' => $zona === 'hijau',
            ])>
                @if ($zona === 'merah')
                    ZONA MERAH - Gawat Darurat
                @elseif ($zona === 'kuning')
                    ZONA KUNING - Waspada
                @else
                    ZONA HIJAU - Normal
                @endif
            </div>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                @foreach ([
                    'RR' => $skor_per_param['skor_respirasi'] ?? 0,
                    'SpO2' => $skor_per_param['skor_saturasi'] ?? 0,
                    'O2+' => $skor_per_param['skor_oksigen'] ?? 0,
                    'Suhu' => $skor_per_param['skor_suhu'] ?? 0,
                    'TD' => $skor_per_param['skor_td'] ?? 0,
                    'Nadi' => $skor_per_param['skor_nadi'] ?? 0,
                    'AVPU' => $skor_per_param['skor_kesadaran'] ?? 0,
                ] as $label => $skor)
                    <span @class([
                        'rounded px-2 py-1 text-xs font-bold',
                        'bg-red-500 text-white' => $skor >= 3,
                        'bg-orange-400 text-white' => $skor === 2,
                        'bg-yellow-300 text-gray-800' => $skor === 1,
                        'bg-green-200 text-gray-800' => $skor === 0,
                    ])>{{ $label }}: {{ $skor }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <form wire:submit.prevent="kirimRujukan" class="space-y-6">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-700">Data Pasien</h2>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Nama Pasien *</label>
                    <input type="text" wire:model="nama_pasien" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('nama_pasien') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">No Rekam Medis *</label>
                    <input type="text" wire:model="no_rm" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('no_rm') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Tanggal Lahir *</label>
                    <input type="date" wire:model="tanggal_lahir" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('tanggal_lahir') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Jenis Kelamin *</label>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm text-gray-700 transition hover:border-blue-500">
                            <input type="radio" wire:model="jenis_kelamin" value="L" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                            <span>Laki-laki</span>
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm text-gray-700 transition hover:border-blue-500">
                            <input type="radio" wire:model="jenis_kelamin" value="P" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                            <span>Perempuan</span>
                        </label>
                    </div>
                    @error('jenis_kelamin') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Waktu Penilaian *</label>
                    <input type="datetime-local" wire:model="waktu_penilaian" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-700">Vital Sign</h2>
            <div class="grid gap-4">
                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">Respirasi (RR)</div>
                            <div class="text-sm text-gray-600">Pilih rentang yang sesuai</div>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        @foreach ([
                            ['label' => '> 25', 'value' => '26', 'score' => 3],
                            ['label' => '21 - 24', 'value' => '22', 'score' => 2],
                            ['label' => '12 - 20', 'value' => '16', 'score' => 0],
                            ['label' => '9 - 11', 'value' => '10', 'score' => 1],
                            ['label' => '< 8', 'value' => '7', 'score' => 3],
                        ] as $option)
                            <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                                <span class="inline-flex items-center gap-3">
                                    <input type="radio" wire:model.live="respirasi" value="{{ $option['value'] }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ $option['label'] }}</span>
                                </span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-red-100 text-red-800' => $option['score'] === 3,
                                    'bg-yellow-100 text-yellow-800' => $option['score'] === 2,
                                    'bg-green-100 text-green-800' => $option['score'] === 1,
                                    'bg-slate-100 text-slate-700' => $option['score'] === 0,
                                ])>{{ $option['score'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('respirasi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">Saturasi O<sub>2</sub></div>
                            <div class="text-sm text-gray-600">Pilih rentang yang pas</div>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        @foreach ([
                            ['label' => '≥ 96%', 'value' => '96', 'score' => 0],
                            ['label' => '94 - 95%', 'value' => '94', 'score' => 1],
                            ['label' => '92 - 93%', 'value' => '92', 'score' => 2],
                            ['label' => '≤ 91%', 'value' => '91', 'score' => 3],
                        ] as $option)
                            <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                                <span class="inline-flex items-center gap-3">
                                    <input type="radio" wire:model.live="saturasi_o2" value="{{ $option['value'] }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ $option['label'] }}</span>
                                </span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-red-100 text-red-800' => $option['score'] === 3,
                                    'bg-yellow-100 text-yellow-800' => $option['score'] === 2,
                                    'bg-green-100 text-green-800' => $option['score'] === 1,
                                    'bg-slate-100 text-slate-700' => $option['score'] === 0,
                                ])>{{ $option['score'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('saturasi_o2') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">Suhu (°C)</div>
                            <div class="text-sm text-gray-600">Pilih rentang</div>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        @foreach ([
                            ['label' => '> 39.0', 'value' => '39.1', 'score' => 2],
                            ['label' => '38.1 - 39.0', 'value' => '38.5', 'score' => 1],
                            ['label' => '36.1 - 38.0', 'value' => '37.0', 'score' => 0],
                            ['label' => '35.1 - 36.0', 'value' => '35.5', 'score' => 1],
                            ['label' => '≤ 35.0', 'value' => '35.0', 'score' => 3],
                        ] as $option)
                            <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                                <span class="inline-flex items-center gap-3">
                                    <input type="radio" wire:model.live="suhu" value="{{ $option['value'] }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ $option['label'] }}</span>
                                </span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-red-100 text-red-800' => $option['score'] === 3,
                                    'bg-yellow-100 text-yellow-800' => $option['score'] === 2,
                                    'bg-green-100 text-green-800' => $option['score'] === 1,
                                    'bg-slate-100 text-slate-700' => $option['score'] === 0,
                                ])>{{ $option['score'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('suhu') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">TD Sistolik</div>
                            <div class="text-sm text-gray-600">Pilih rentang</div>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        @foreach ([
                            ['label' => '> 220', 'value' => '221', 'score' => 3],
                            ['label' => '201 - 219', 'value' => '210', 'score' => 2],
                            ['label' => '180 - 200', 'value' => '190', 'score' => 1],
                            ['label' => '100 - 179', 'value' => '120', 'score' => 0],
                            ['label' => '96 - 99', 'value' => '98', 'score' => 1],
                            ['label' => '86 - 95', 'value' => '90', 'score' => 2],
                            ['label' => '≤ 85', 'value' => '85', 'score' => 3],
                        ] as $option)
                            <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                                <span class="inline-flex items-center gap-3">
                                    <input type="radio" wire:model.live="td_sistolik" value="{{ $option['value'] }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ $option['label'] }}</span>
                                </span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-red-100 text-red-800' => $option['score'] === 3,
                                    'bg-yellow-100 text-yellow-800' => $option['score'] === 2,
                                    'bg-green-100 text-green-800' => $option['score'] === 1,
                                    'bg-slate-100 text-slate-700' => $option['score'] === 0,
                                ])>{{ $option['score'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('td_sistolik') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">Nadi</div>
                            <div class="text-sm text-gray-600">Pilih rentang</div>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        @foreach ([
                            ['label' => '> 130', 'value' => '131', 'score' => 3],
                            ['label' => '111 - 130', 'value' => '120', 'score' => 2],
                            ['label' => '91 - 110', 'value' => '100', 'score' => 1],
                            ['label' => '51 - 90', 'value' => '70', 'score' => 0],
                            ['label' => '41 - 50', 'value' => '45', 'score' => 1],
                            ['label' => '≤ 40', 'value' => '40', 'score' => 3],
                        ] as $option)
                            <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                                <span class="inline-flex items-center gap-3">
                                    <input type="radio" wire:model.live="nadi" value="{{ $option['value'] }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ $option['label'] }}</span>
                                </span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-red-100 text-red-800' => $option['score'] === 3,
                                    'bg-yellow-100 text-yellow-800' => $option['score'] === 2,
                                    'bg-green-100 text-green-800' => $option['score'] === 1,
                                    'bg-slate-100 text-slate-700' => $option['score'] === 0,
                                ])>{{ $option['score'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('nadi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">Suplement O<sub>2</sub></div>
                            <div class="text-sm text-gray-600">Pilih apakah pasien menerima oksigen tambahan</div>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="oksigen_tambahan" value="0" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <span>Tidak</span>
                            </span>
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">0</span>
                        </label>
                        <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="oksigen_tambahan" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <span>Ya</span>
                            </span>
                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">2</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase text-gray-500">Kesadaran AVPU</div>
                            <div class="text-sm text-gray-600">Pilih status kesadaran</div>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="kesadaran" value="A" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <span>Sadar</span>
                            </span>
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">0</span>
                        </label>
                        <label class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition hover:border-blue-500">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="kesadaran" value="V" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <span>V / P / U</span>
                            </span>
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">3</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-700">Catatan Rujukan</h2>
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Riwayat Singkat & Keluhan</label>
                    <textarea wire:model="catatan_rujukan" rows="3" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Tindakan yang Sudah Diberikan</label>
                    <textarea wire:model="tindakan_yang_diberikan" rows="2" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" @disabled($sedangMengirim) @class([
                'rounded-xl px-8 py-3 font-semibold text-white transition',
                'bg-red-600 hover:bg-red-700' => $zona === 'merah',
                'bg-yellow-500 hover:bg-yellow-600' => $zona === 'kuning',
                'bg-blue-600 hover:bg-blue-700' => ! in_array($zona, ['kuning', 'merah'], true),
            ])>
                <span wire:loading.remove>{{ $sedangMengirim ? 'Mengirim...' : 'Kirim Rujukan' }}</span>
                <span wire:loading>Mengirim...</span>
            </button>
        </div>
    </form>
</div>
