<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DetailRiwayatEws extends Component
{
    public EwsAssessment $assessment;

    public function mount(EwsAssessment $assessment): void
    {
        $this->assessment = $assessment->load(['patient', 'faskes', 'petugas', 'penangananOleh', 'feedbackOleh']);
        
        $user = Auth::user();
        if ($user->hasRole(['puskesmas', 'rs_perujuk']) && $this->assessment->faskes_id !== $user->faskes_id) {
            abort(403, 'Akses ditolak.');
        }
    }

    public function render()
    {
        return view('livewire.detail-riwayat-ews')
            ->layout('layouts.app', ['title' => 'Detail Riwayat Rujukan EWS']);
    }
}
