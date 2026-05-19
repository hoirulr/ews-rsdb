<?php

namespace App\Livewire;

use App\Services\RekapRujukanIgdService;
use Livewire\Component;

class RekapRujukanIgd extends Component
{
    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public function mount(): void
    {
        $this->tanggalMulai = now()->startOfMonth()->toDateString();
        $this->tanggalSelesai = now()->toDateString();
    }

    public function resetFilter(): void
    {
        $this->tanggalMulai = now()->startOfMonth()->toDateString();
        $this->tanggalSelesai = now()->toDateString();
    }

    public function render(RekapRujukanIgdService $service)
    {
        $rekap = $service->get($this->tanggalMulai, $this->tanggalSelesai);

        return view('livewire.rekap-rujukan-igd', [
            'rekap' => $rekap,
            'totalHijau' => $rekap->sum('total_hijau'),
            'totalKuning' => $rekap->sum('total_kuning'),
            'totalMerah' => $rekap->sum('total_merah'),
            'totalRujukan' => $rekap->sum('total_rujukan'),
            'exportUrl' => route('igd.rekap-rujukan.export', [
                'tanggal_mulai' => $this->tanggalMulai,
                'tanggal_selesai' => $this->tanggalSelesai,
            ]),
        ])->layout('layouts.app', ['title' => 'Rekap Rujukan IGD']);
    }
}
