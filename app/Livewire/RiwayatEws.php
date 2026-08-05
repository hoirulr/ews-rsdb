<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Livewire\Component;
use Livewire\WithPagination;

class RiwayatEws extends Component
{
    use WithPagination;

    public function render()
    {
        $query = EwsAssessment::with(['patient', 'faskes', 'petugas', 'feedbackOleh'])->latest('waktu_penilaian');

        if (auth()->user()->hasRole(['puskesmas', 'rs_perujuk'])) {
            $query->where('faskes_id', auth()->user()->faskes_id);
        }

        return view('livewire.riwayat-ews', [
            'assessments' => $query->paginate(25),
        ])->layout('layouts.app', ['title' => 'Riwayat Rujukan EWS']);
    }
}
