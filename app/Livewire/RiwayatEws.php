<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Livewire\Component;

class RiwayatEws extends Component
{
    public string $search = '';

    public function resetPencarian(): void
    {
        $this->search = '';
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
            'assessments' => $query->take(50)->get(),
        ])->layout('layouts.app', ['title' => 'Riwayat Rujukan EWS']);
    }
}
