<div class="mx-auto max-w-4xl space-y-6"
     x-data="{
         validationErrors: [],
         closeAndScrollToError() {
             this.$dispatch('close-modal', 'validation-error');
             if (this.validationErrors.length > 0) {
                 const firstField = this.validationErrors[0].field;
                 const el = document.querySelector('[wire\\:model=\'' + firstField + '\'], [wire\\:model\\.live=\'' + firstField + '\']');
                 if (el) {
                     el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                     el.focus();
                 }
             }
         }
     }"
     x-on:form-validation-error.window="validationErrors = $event.detail.errors; $dispatch('open-modal', 'validation-error')">

    {{-- Validation Error Popup Modal --}}
    @teleport('body')
        <x-modal name="validation-error" maxWidth="2xl" :center="true" :sidebarAdjust="true">
            <div class="p-6">
                {{-- Header --}}
                <div class="flex items-center gap-3 border-b pb-4 mb-4 dark:border-gray-800">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-error-100 text-error-600 dark:bg-error-500/20 dark:text-error-400">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Form Belum Lengkap</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Lengkapi data sebelum mengirim rujukan</p>
                    </div>
                </div>

                {{-- Message --}}
                <div class="py-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Mohon lengkapi seluruh field wajib yang ditandai dengan tanda bintang (*) sebelum mengirim rujukan.
                    </p>
                </div>

                {{-- Footer --}}
                <div class="mt-6 flex justify-end">
                    <button type="button"
                            @click="closeAndScrollToError()"
                            class="w-full rounded-lg bg-error-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-error-700 focus:outline-none focus:ring-2 focus:ring-error-500 focus:ring-offset-2">
                        Lengkapi Sekarang
                    </button>
                </div>
            </div>
        </x-modal>
    @endteleport

    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Form Rujukan EWS</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->faskes->nama_faskes ?? '' }}</p>
    </div>

    @if ($sukses)
        <div @class([
            'rounded-xl border p-4',
            'border-error-400 bg-error-100 text-error-800 dark:border-error-700 dark:bg-error-500/20 dark:text-error-300' => $zona_terkirim === 'merah',
            'border-warning-400 bg-warning-100 text-warning-800 dark:border-warning-700 dark:bg-warning-500/20 dark:text-warning-300' => $zona_terkirim === 'kuning',
            'border-success-400 bg-success-100 text-success-800 dark:border-success-700 dark:bg-success-500/20 dark:text-success-300' => $zona_terkirim === 'hijau' || $zona_terkirim === '',
        ])>
            {{ $pesanSukses }}
        </div>
    @endif

    @if ($pesanError)
        <div class="rounded-xl border border-error-400 bg-error-100 p-4 text-error-800 dark:border-error-700 dark:bg-error-500/20 dark:text-error-300">
            {{ $pesanError }}
        </div>
    @endif

    @if ($zona)
        <div @class([
            'rounded-xl border-2 p-6 text-center',
            'border-error-400 bg-error-50 dark:border-error-600 dark:bg-error-500/10' => $zona === 'merah',
            'border-warning-400 bg-warning-50 dark:border-warning-600 dark:bg-warning-500/10' => $zona === 'kuning',
            'border-success-400 bg-success-50 dark:border-success-600 dark:bg-success-500/10' => $zona === 'hijau',
        ])>
            <div @class([
                'text-6xl font-black',
                'text-error-600 dark:text-error-400' => $zona === 'merah',
                'text-warning-600 dark:text-warning-400' => $zona === 'kuning',
                'text-success-600 dark:text-success-400' => $zona === 'hijau',
            ])>{{ $total_skor }}</div>
            <div @class([
                'mt-1 text-lg font-bold',
                'text-error-700 dark:text-error-300' => $zona === 'merah',
                'text-warning-700 dark:text-warning-300' => $zona === 'kuning',
                'text-success-700 dark:text-success-300' => $zona === 'hijau',
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
                        'bg-error-500 text-white' => $skor >= 3,
                        'bg-orange-400 text-white' => $skor === 2,
                        'bg-warning-300 text-gray-800' => $skor === 1,
                        'bg-success-200 text-gray-800' => $skor === 0,
                    ])>{{ $label }}: {{ $skor }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <form wire:submit.prevent="kirimRujukan" class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="mb-4 text-lg font-semibold text-gray-700 dark:text-white/90">Data Pasien</h2>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">Nama Pasien *</label>
                    <input type="text" wire:model="nama_pasien" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                    @error('nama_pasien') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">No Rekam Medis *</label>
                    <input type="text" wire:model="no_rm" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                    @error('no_rm') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">Tanggal Lahir *</label>
                    <div x-data="{
                            date: @entangle('tanggal_lahir'),
                            fp: null,
                            init() {
                                this.fp = flatpickr(this.$refs.input, {
                                    altInput: true,
                                    altFormat: 'd-m-Y',
                                    dateFormat: 'Y-m-d',
                                    defaultDate: this.date,
                                    onChange: (selectedDates, dateStr) => {
                                        this.date = dateStr;
                                    }
                                });
                                this.$watch('date', value => {
                                    if (!value) {
                                        this.fp.clear();
                                    } else {
                                        this.fp.setDate(value);
                                    }
                                });
                            }
                        }" 
                        class="relative" 
                        wire:ignore>

                        <input x-ref="input" type="text" placeholder="YYYY-MM-DD" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                    </div>
                    @error('tanggal_lahir') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">Jenis Kelamin *</label>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm text-gray-700 transition hover:border-brand-300 dark:border-gray-800 dark:text-gray-300 dark:hover:border-brand-800">
                            <input type="radio" wire:model="jenis_kelamin" value="L" class="h-4 w-4 text-brand-500 focus:ring-brand-500" />
                            <span>Laki-laki</span>
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm text-gray-700 transition hover:border-brand-300 dark:border-gray-800 dark:text-gray-300 dark:hover:border-brand-800">
                            <input type="radio" wire:model="jenis_kelamin" value="P" class="h-4 w-4 text-brand-500 focus:ring-brand-500" />
                            <span>Perempuan</span>
                        </label>
                    </div>
                    @error('jenis_kelamin') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">Waktu Penilaian *</label>
                    <input type="datetime-local" wire:model="waktu_penilaian" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="mb-4 text-lg font-semibold text-gray-700 dark:text-white/90">Vital Sign</h2>
            <div class="grid gap-4">
                @php
                    $vitalSigns = [
                        ['name' => 'respirasi', 'label' => 'Respirasi (RR)', 'desc' => 'Pilih rentang yang sesuai', 'options' => [
                            ['label' => '> 25', 'value' => '26', 'score' => 3],
                            ['label' => '21 - 24', 'value' => '22', 'score' => 2],
                            ['label' => '12 - 20', 'value' => '16', 'score' => 0],
                            ['label' => '9 - 11', 'value' => '10', 'score' => 1],
                            ['label' => '< 8', 'value' => '7', 'score' => 3],
                        ]],
                        ['name' => 'saturasi_o2', 'label' => 'Saturasi O₂', 'desc' => 'Pilih rentang yang pas', 'options' => [
                            ['label' => '≥ 96%', 'value' => '96', 'score' => 0],
                            ['label' => '94 - 95%', 'value' => '94', 'score' => 1],
                            ['label' => '92 - 93%', 'value' => '92', 'score' => 2],
                            ['label' => '≤ 91%', 'value' => '91', 'score' => 3],
                        ]],
                        ['name' => 'suhu', 'label' => 'Suhu (°C)', 'desc' => 'Pilih rentang', 'options' => [
                            ['label' => '> 39.0', 'value' => '39.1', 'score' => 2],
                            ['label' => '38.1 - 39.0', 'value' => '38.5', 'score' => 1],
                            ['label' => '36.1 - 38.0', 'value' => '37.0', 'score' => 0],
                            ['label' => '35.1 - 36.0', 'value' => '35.5', 'score' => 1],
                            ['label' => '≤ 35.0', 'value' => '35.0', 'score' => 3],
                        ]],
                        ['name' => 'td_sistolik', 'label' => 'TD Sistolik', 'desc' => 'Pilih rentang', 'options' => [
                            ['label' => '> 220', 'value' => '221', 'score' => 3],
                            ['label' => '201 - 219', 'value' => '210', 'score' => 2],
                            ['label' => '180 - 200', 'value' => '190', 'score' => 1],
                            ['label' => '100 - 179', 'value' => '120', 'score' => 0],
                            ['label' => '96 - 99', 'value' => '98', 'score' => 1],
                            ['label' => '86 - 95', 'value' => '90', 'score' => 2],
                            ['label' => '≤ 85', 'value' => '85', 'score' => 3],
                        ]],
                        ['name' => 'nadi', 'label' => 'Nadi', 'desc' => 'Pilih rentang', 'options' => [
                            ['label' => '> 130', 'value' => '131', 'score' => 3],
                            ['label' => '111 - 130', 'value' => '120', 'score' => 2],
                            ['label' => '91 - 110', 'value' => '100', 'score' => 1],
                            ['label' => '51 - 90', 'value' => '70', 'score' => 0],
                            ['label' => '41 - 50', 'value' => '45', 'score' => 1],
                            ['label' => '≤ 40', 'value' => '40', 'score' => 3],
                        ]],
                    ];
                @endphp

                @foreach ($vitalSigns as $vital)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="mb-3">
                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ $vital['label'] }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-500">{{ $vital['desc'] }}</div>
                        </div>
                        <div class="grid gap-2">
                            @foreach ($vital['options'] as $option)
                                <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm transition hover:border-brand-300 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-brand-700">
                                    <span class="inline-flex items-center gap-3">
                                        <input type="radio" wire:model.live="{{ $vital['name'] }}" value="{{ $option['value'] }}" class="h-4 w-4 text-brand-500 focus:ring-brand-500" />
                                        <span class="text-gray-700 dark:text-gray-300">{{ $option['label'] }}</span>
                                    </span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-error-100 text-error-800 dark:bg-error-500/20 dark:text-error-400' => $option['score'] === 3,
                                        'bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-400' => $option['score'] === 2,
                                        'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400' => $option['score'] === 1,
                                        'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-400' => $option['score'] === 0,
                                    ])>{{ $option['score'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error($vital['name']) <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                {{-- Suplemen Oksigen --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <div class="mb-3">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Suplement O₂</div>
                        <div class="text-sm text-gray-600 dark:text-gray-500">Pilih apakah pasien menerima oksigen tambahan</div>
                    </div>
                    <div class="grid gap-2">
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm transition hover:border-brand-300 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-brand-700">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="oksigen_tambahan" value="0" class="h-4 w-4 text-brand-500 focus:ring-brand-500" />
                                <span class="text-gray-700 dark:text-gray-300">Tidak</span>
                            </span>
                            <span class="rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-800 dark:bg-success-500/20 dark:text-success-400">0</span>
                        </label>
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm transition hover:border-brand-300 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-brand-700">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="oksigen_tambahan" value="1" class="h-4 w-4 text-brand-500 focus:ring-brand-500" />
                                <span class="text-gray-700 dark:text-gray-300">Ya</span>
                            </span>
                            <span class="rounded-full bg-warning-100 px-2 py-0.5 text-xs font-semibold text-warning-800 dark:bg-warning-500/20 dark:text-warning-400">2</span>
                        </label>
                    </div>
                </div>

                {{-- Kesadaran AVPU --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <div class="mb-3">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Kesadaran AVPU</div>
                        <div class="text-sm text-gray-600 dark:text-gray-500">Pilih status kesadaran</div>
                    </div>
                    <div class="grid gap-2">
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm transition hover:border-brand-300 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-brand-700">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="kesadaran" value="A" class="h-4 w-4 text-brand-500 focus:ring-brand-500" />
                                <span class="text-gray-700 dark:text-gray-300">Sadar</span>
                            </span>
                            <span class="rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-800 dark:bg-success-500/20 dark:text-success-400">0</span>
                        </label>
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm transition hover:border-brand-300 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-brand-700">
                            <span class="inline-flex items-center gap-3">
                                <input type="radio" wire:model.live="kesadaran" value="V" class="h-4 w-4 text-brand-500 focus:ring-brand-500" />
                                <span class="text-gray-700 dark:text-gray-300">V / P / U</span>
                            </span>
                            <span class="rounded-full bg-error-100 px-2 py-0.5 text-xs font-semibold text-error-800 dark:bg-error-500/20 dark:text-error-400">3</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="mb-4 text-lg font-semibold text-gray-700 dark:text-white/90">Catatan Rujukan</h2>
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">Riwayat Singkat & Keluhan</label>
                    <textarea wire:model="catatan_rujukan" rows="3" class="dark:bg-dark-900 w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-400">Tindakan yang Sudah Diberikan</label>
                    <textarea wire:model="tindakan_yang_diberikan" rows="2" class="dark:bg-dark-900 w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" @disabled($sedangMengirim) @class([
                'rounded-lg px-8 py-3 font-semibold text-white transition shadow-theme-xs',
                'bg-error-600 hover:bg-error-700' => $zona === 'merah',
                'bg-warning-500 hover:bg-warning-600' => $zona === 'kuning',
                'bg-brand-500 hover:bg-brand-600' => ! in_array($zona, ['kuning', 'merah'], true),
            ])>
                <span wire:loading.remove>{{ $sedangMengirim ? 'Mengirim...' : 'Kirim Rujukan' }}</span>
                <span wire:loading>Mengirim...</span>
            </button>
        </div>
    </form>
</div>
