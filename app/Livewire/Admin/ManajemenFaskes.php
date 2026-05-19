<?php

namespace App\Livewire\Admin;

use App\Models\Faskes;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManajemenFaskes extends Component
{
    public string $nama_faskes = '';

    public string $tipe = 'puskesmas';

    public string $kode_faskes = '';

    public string $alamat = '';

    public string $no_telp = '';

    public bool $is_active = true;

    public ?int $faskesEditId = null;

    public string $editNamaFaskes = '';

    public string $editTipe = 'puskesmas';

    public string $editKodeFaskes = '';

    public string $editAlamat = '';

    public string $editNoTelp = '';

    public bool $editIsActive = true;

    public string $pesanSukses = '';

    public function simpanFaskes(): void
    {
        $validated = $this->validate([
            'nama_faskes' => ['required', 'string', 'max:255'],
            'tipe' => ['required', Rule::in(['rsud', 'puskesmas', 'rs_perujuk'])],
            'kode_faskes' => ['required', 'string', 'max:255', Rule::unique('faskes', 'kode_faskes')],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Faskes::create($validated);

        $this->reset(['nama_faskes', 'kode_faskes', 'alamat', 'no_telp']);
        $this->tipe = 'puskesmas';
        $this->is_active = true;
        $this->pesanSukses = 'Faskes berhasil ditambahkan.';
    }

    public function bukaEditFaskes(int $faskesId): void
    {
        $faskes = Faskes::query()->findOrFail($faskesId);

        $this->faskesEditId = $faskes->id;
        $this->editNamaFaskes = $faskes->nama_faskes;
        $this->editTipe = $faskes->tipe;
        $this->editKodeFaskes = $faskes->kode_faskes;
        $this->editAlamat = $faskes->alamat ?? '';
        $this->editNoTelp = $faskes->no_telp ?? '';
        $this->editIsActive = (bool) $faskes->is_active;
        $this->resetValidation([
            'editNamaFaskes',
            'editTipe',
            'editKodeFaskes',
            'editAlamat',
            'editNoTelp',
            'editIsActive',
        ]);

        $this->dispatch('open-modal', 'edit-faskes');
    }

    public function simpanEditFaskes(): void
    {
        $faskes = Faskes::query()->findOrFail($this->faskesEditId);

        $validated = $this->validate([
            'editNamaFaskes' => ['required', 'string', 'max:255'],
            'editTipe' => ['required', Rule::in(['rsud', 'puskesmas', 'rs_perujuk'])],
            'editKodeFaskes' => ['required', 'string', 'max:255', Rule::unique('faskes', 'kode_faskes')->ignore($faskes)],
            'editAlamat' => ['nullable', 'string', 'max:255'],
            'editNoTelp' => ['nullable', 'string', 'max:255'],
            'editIsActive' => ['boolean'],
        ]);

        $faskes->update([
            'nama_faskes' => $validated['editNamaFaskes'],
            'tipe' => $validated['editTipe'],
            'kode_faskes' => $validated['editKodeFaskes'],
            'alamat' => $validated['editAlamat'],
            'no_telp' => $validated['editNoTelp'],
            'is_active' => $validated['editIsActive'],
        ]);

        $this->reset([
            'faskesEditId',
            'editNamaFaskes',
            'editKodeFaskes',
            'editAlamat',
            'editNoTelp',
        ]);
        $this->editTipe = 'puskesmas';
        $this->editIsActive = true;
        $this->pesanSukses = 'Faskes berhasil diperbarui.';

        $this->dispatch('close-modal', 'edit-faskes');
    }

    public function render()
    {
        return view('livewire.admin.manajemen-faskes', [
            'faskesList' => Faskes::orderBy('nama_faskes')->get(),
        ])->layout('layouts.app', ['title' => 'Manajemen Faskes']);
    }
}
