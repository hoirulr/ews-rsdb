<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Livewire\Component;
use Livewire\WithPagination;

class DaftarRujukanRumahSakit extends Component
{
    use WithPagination;

    public string $status = 'semua';

    public string $zona = 'semua';

    public string $search = '';

    public function resetFilter(): void
    {
        $this->status = 'semua';
        $this->zona = 'semua';
        $this->search = '';
        $this->resetPage();
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['status', 'zona', 'search'], true)) {
            $this->resetPage();
        }
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

        // Statistik dihitung lewat query agregat agar tidak memuat seluruh
        // tabel; daftar tabelnya sendiri dipaginasi.
        $statistik = $query->clone()
            ->reorder()
            ->selectRaw("count(*) as total")
            ->selectRaw("sum(status = 'ditangani') as total_ditangani")
            ->selectRaw("sum(status = 'selesai') as total_selesai")
            ->selectRaw('sum(feedback_hasil is null) as total_belum_feedback')
            ->first();

        $rujukan = $query->paginate(25);

        $chartQuery = EwsAssessment::whereMonth('waktu_penilaian', now()->month)
            ->whereYear('waktu_penilaian', now()->year);

        $rujukanPerZonaRaw = $chartQuery->clone()
            ->selectRaw('zona, count(*) as count')
            ->groupBy('zona')
            ->pluck('count', 'zona')
            ->toArray();

        $rujukanPerZona = [
            'hijau' => $rujukanPerZonaRaw['hijau'] ?? 0,
            'kuning' => $rujukanPerZonaRaw['kuning'] ?? 0,
            'merah' => $rujukanPerZonaRaw['merah'] ?? 0,
        ];

        $rujukanPerFaskes = $chartQuery->clone()
            ->join('faskes', 'ews_assessments.faskes_id', '=', 'faskes.id')
            ->selectRaw('faskes.nama_faskes as label, count(ews_assessments.id) as count')
            ->groupBy('faskes.id', 'faskes.nama_faskes')
            ->pluck('count', 'label')
            ->toArray();

        return view('livewire.daftar-rujukan-rumah-sakit', [
            'rujukanDitangani' => $rujukan,
            'totalRujukan' => (int) ($statistik->total ?? 0),
            'totalDitangani' => (int) ($statistik->total_ditangani ?? 0),
            'totalSelesai' => (int) ($statistik->total_selesai ?? 0),
            'totalBelumFeedback' => (int) ($statistik->total_belum_feedback ?? 0),
            'rujukanPerZona' => $rujukanPerZona,
            'rujukanPerFaskes' => $rujukanPerFaskes,
        ])->layout('layouts.app', ['title' => 'Daftar Rujukan Rumah Sakit']);
    }
}
