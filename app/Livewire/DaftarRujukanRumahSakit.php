<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Livewire\Component;

class DaftarRujukanRumahSakit extends Component
{
    public string $status = 'semua';

    public string $zona = 'semua';

    public string $search = '';

    public function resetFilter(): void
    {
        $this->status = 'semua';
        $this->zona = 'semua';
        $this->search = '';
    }

    public function render()
    {
        $query = EwsAssessment::with(['patient', 'faskes', 'petugas', 'penangananOleh', 'feedbackOleh'])
            ->whereIn('status', ['ditangani', 'selesai'])
            ->latest('waktu_penilaian');

        if ($this->status !== 'semua') {
            $query->where('status', $this->status);
        }

        if ($this->zona !== 'semua') {
            $query->where('zona', $this->zona);
        }

        if ($this->search !== '') {
            $query->where(function ($subQuery): void {
                $subQuery->whereHas('patient', function ($patientQuery): void {
                    $patientQuery
                        ->where('nama_pasien', 'like', '%'.$this->search.'%')
                        ->orWhere('no_rm', 'like', '%'.$this->search.'%');
                })->orWhereHas('faskes', function ($faskesQuery): void {
                    $faskesQuery->where('nama_faskes', 'like', '%'.$this->search.'%');
                });
            });
        }

        $rujukan = $query->get();

        return view('livewire.daftar-rujukan-rumah-sakit', [
            'rujukanDitangani' => $rujukan,
            'totalRujukan' => $rujukan->count(),
            'totalDitangani' => $rujukan->where('status', 'ditangani')->count(),
            'totalSelesai' => $rujukan->where('status', 'selesai')->count(),
            'totalBelumFeedback' => $rujukan->whereNull('feedback_hasil')->count(),
        ])->layout('layouts.app', ['title' => 'Daftar Rujukan Rumah Sakit']);
    }
}
