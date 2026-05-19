<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Faskes</h1>
        <p class="text-sm text-gray-500">Daftar fasilitas kesehatan sistem EWS.</p>
    </div>

    @if ($pesanSukses)
        <div class="mb-4 rounded-lg border border-green-400 bg-green-100 p-4 text-green-800">{{ $pesanSukses }}</div>
    @endif

    <form wire:submit.prevent="simpanFaskes" class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">Tambah Faskes</h2>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Nama Faskes *</label>
                <input type="text" wire:model="nama_faskes" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('nama_faskes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Kode Faskes *</label>
                <input type="text" wire:model="kode_faskes" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('kode_faskes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Tipe *</label>
                <select wire:model="tipe" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="puskesmas">Puskesmas</option>
                    <option value="rs_perujuk">RS Perujuk</option>
                    <option value="rsud">RSUD</option>
                </select>
                @error('tipe') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">No Telp</label>
                <input type="text" wire:model="no_telp" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('no_telp') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-600">Alamat</label>
                <input type="text" wire:model="alamat" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('alamat') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                Aktif
            </label>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">
                Tambah Faskes
            </button>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($faskesList as $faskes)
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-bold text-gray-800">{{ $faskes->nama_faskes }}</h2>
                        <p class="text-sm text-gray-500">{{ $faskes->kode_faskes }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">{{ $faskes->tipe_label }}</span>
                </div>
                <p class="mt-3 text-sm text-gray-600">{{ $faskes->alamat ?: 'Alamat belum diisi' }}</p>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                    <span>{{ $faskes->no_telp ?: 'No telp belum diisi' }}</span>
                    <span>{{ $faskes->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" wire:click="bukaEditFaskes({{ $faskes->id }})" class="rounded-lg bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-200">
                        Edit
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <x-modal name="edit-faskes" maxWidth="2xl">
        <form wire:submit.prevent="simpanEditFaskes" class="p-6">
            <h2 class="text-lg font-semibold text-gray-800">Edit Faskes</h2>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Nama Faskes *</label>
                    <input type="text" wire:model="editNamaFaskes" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('editNamaFaskes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Kode Faskes *</label>
                    <input type="text" wire:model="editKodeFaskes" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('editKodeFaskes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Tipe *</label>
                    <select wire:model="editTipe" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="puskesmas">Puskesmas</option>
                        <option value="rs_perujuk">RS Perujuk</option>
                        <option value="rsud">RSUD</option>
                    </select>
                    @error('editTipe') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">No Telp</label>
                    <input type="text" wire:model="editNoTelp" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('editNoTelp') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-600">Alamat</label>
                    <input type="text" wire:model="editAlamat" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('editAlamat') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" wire:model="editIsActive" class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                    Aktif
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close-modal', 'edit-faskes')" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </x-modal>
</div>
