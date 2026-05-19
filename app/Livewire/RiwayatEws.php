<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Livewire\Component;

class RiwayatEws extends Component
{
    public ?EwsAssessment $selectedAssessment = null;

    public function lihatDetail(int $assessmentId): void
    {
        $query = EwsAssessment::with(['patient', 'faskes', 'petugas', 'penangananOleh', 'feedbackOleh'])
            ->whereKey($assessmentId);

        if (auth()->user()->hasRole(['puskesmas', 'rs_perujuk'])) {
            $query->where('faskes_id', auth()->user()->faskes_id);
        }

        $this->selectedAssessment = $query->firstOrFail();

        $this->dispatch('open-modal', 'detail-rujukan');
    }

    public function render()
    {
        $query = EwsAssessment::with(['patient', 'faskes', 'petugas', 'feedbackOleh'])->latest('waktu_penilaian');

        if (auth()->user()->hasRole(['puskesmas', 'rs_perujuk'])) {
            $query->where('faskes_id', auth()->user()->faskes_id);
        }

        return view('livewire.riwayat-ews', [
            'assessments' => $query->take(50)->get(),
        ])->layout('layouts.app', ['title' => 'Riwayat Rujukan EWS']);
    }
}
