<?php

use App\Models\Faskes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $nama_faskes = '';

    public string $kode_faskes = '';

    public string $alamat = '';

    public string $no_telp = '';

    public function mount(): void
    {
        $faskes = Auth::user()->faskes;

        if (! $faskes) {
            return;
        }

        $this->nama_faskes = $faskes->nama_faskes;
        $this->kode_faskes = $faskes->kode_faskes;
        $this->alamat = $faskes->alamat ?? '';
        $this->no_telp = $faskes->no_telp ?? '';
    }

    public function updateFaskesDetail(): void
    {
        $faskes = Auth::user()->faskes;

        abort_if(! $faskes, 403);

        $validated = $this->validate([
            'nama_faskes' => ['required', 'string', 'max:255'],
            'kode_faskes' => ['required', 'string', 'max:255', Rule::unique('faskes', 'kode_faskes')->ignore($faskes)],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:255'],
        ]);

        $faskes->update($validated);

        $this->dispatch('faskes-detail-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Detail Faskes
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Perbarui data fasilitas kesehatan yang terhubung dengan akun Anda.
        </p>
    </header>

    @if (auth()->user()->faskes)
        <form wire:submit="updateFaskesDetail" class="mt-6 space-y-6">
            <div>
                <x-input-label for="nama_faskes" value="Nama Faskes" />
                <x-text-input wire:model="nama_faskes" id="nama_faskes" name="nama_faskes" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('nama_faskes')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="kode_faskes" value="Kode Faskes" />
                <x-text-input wire:model="kode_faskes" id="kode_faskes" name="kode_faskes" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('kode_faskes')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="alamat" value="Alamat" />
                <x-text-input wire:model="alamat" id="alamat" name="alamat" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="no_telp" value="No Telp" />
                <x-text-input wire:model="no_telp" id="no_telp" name="no_telp" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('no_telp')" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>Simpan</x-primary-button>

                <x-action-message class="me-3" on="faskes-detail-updated">
                    Tersimpan.
                </x-action-message>
            </div>
        </form>
    @else
        <p class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            Akun Anda belum terhubung dengan faskes.
        </p>
    @endif
</section>
