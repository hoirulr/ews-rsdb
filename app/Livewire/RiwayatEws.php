<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Livewire\Component;
use Livewire\WithPagination;

class RiwayatEws extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetPencarian(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = EwsAssessment::with(['patient', 'faskes', 'petugas', 'feedbackOleh'])->latest('waktu_penilaian');

        if (auth()->user()->hasRole(['puskesmas', 'rs_perujuk'])) {
            $query->where('faskes_id', auth()->user()->faskes_id);
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);

            $query->where(function ($subQuery) use ($search): void {
                $subQuery->whereHas('patient', function ($patientQuery) use ($search): void {
                    $patientQuery
                        ->where('nama_pasien', 'like', '%'.$search.'%')
                        ->orWhere('no_rm', 'like', '%'.$search.'%');
                })->orWhereHas('faskes', function ($faskesQuery) use ($search): void {
                    $faskesQuery->where('nama_faskes', 'like', '%'.$search.'%');
                });
            });
        }

        return view('livewire.riwayat-ews', [
            'assessments' => $query->paginate(25),
        ])->layout('layouts.app', ['title' => 'Riwayat Rujukan EWS']);
    }
}
