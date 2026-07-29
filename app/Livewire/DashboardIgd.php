<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use App\Services\EwsRujukanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class DashboardIgd extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $alertAktif = [];

    public bool $alarmAktif = false;

    public function mount(): void
    {
        $this->muatAlert();
        $this->bunyikanAlarmJikaAda();
    }

    public function muatAlert(): void
    {
        $aktif = EwsAssessment::with(['patient', 'faskes', 'petugas'])
            ->where('alert_aktif', true)
            ->whereIn('zona', ['kuning', 'merah'])
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $this->alertAktif = $aktif->map(fn (EwsAssessment $assessment): array => [
            'id' => $assessment->id,
            'nama_pasien' => $assessment->patient->nama_pasien,
            'no_rm' => $assessment->patient->no_rm,
            'faskes_asal' => $assessment->faskes->nama_faskes,
            'total_skor' => $assessment->total_skor,
            'zona' => $assessment->zona,
            'zona_label' => $assessment->zona_label,
            'waktu' => $assessment->waktu_penilaian->format('d/m/Y H:i'),
            'petugas' => $assessment->petugas->name,
            'catatan' => $assessment->catatan_rujukan,
            'respon_klinik' => $assessment->respon_klinik,
        ])->toArray();

        $this->alarmAktif = count($this->alertAktif) > 0;
    }

    #[On('echo-private:ews-alerts.rsud,.ews.alert')]
    public function alertBaru(array $data): void
    {
        // Avoid duplicates if poll already picked it up
        $existingIds = array_column($this->alertAktif, 'id');
        if (in_array($data['id'] ?? null, $existingIds, true)) {
            return;
        }

        array_unshift($this->alertAktif, $data);
        $this->alarmAktif = true;

        $this->dispatch('bunyikan-alarm', zona: $data['zona']);
    }

    public function tanganiAlert(int $assessmentId, EwsRujukanService $service): void
    {
        try {
            $service->tanganiAlert($assessmentId, Auth::user());

            $this->alertAktif = array_values(
                array_filter($this->alertAktif, fn (array $alert): bool => $alert['id'] !== $assessmentId),
            );

            if ($this->alertAktif === []) {
                $this->alarmAktif = false;
                $this->dispatch('hentikan-alarm');
            }

            $this->dispatch('alert-ditangani', id: $assessmentId);
        } catch (RuntimeException $exception) {
            $this->dispatch(
                'notification',
                type: 'warning',
                message: $exception->getMessage(),
            );

            $this->muatAlert();
        }
    }

    public function render()
    {
        // Capture IDs before refresh to detect new alerts from DB polling
        $idSebelumnya = array_column($this->alertAktif, 'id');

        // Refresh alerts from DB on every poll cycle (fallback if WebSocket fails)
        $this->muatAlert();

        // Detect new alerts that appeared since last render
        $idSekarang = array_column($this->alertAktif, 'id');
        $idBaru = array_diff($idSekarang, $idSebelumnya);

        if ($idBaru !== []) {
            $this->bunyikanAlarmJikaAda();
        }

        // Halaman ini di-poll tiap 10 detik, jadi daftar dibatasi dan statistik
        // dihitung lewat satu query agregat agar tetap ringan saat data membesar.
        $rujukanBelumDitangani = EwsAssessment::with(['patient', 'faskes', 'petugas'])
            ->where('status', 'menunggu')
            ->latest('waktu_penilaian')
            ->take(50)
            ->get();

        $statistik = EwsAssessment::where('status', 'menunggu')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(zona = 'merah') as total_merah")
            ->selectRaw("sum(zona = 'kuning') as total_kuning")
            ->selectRaw("sum(zona = 'hijau') as total_hijau")
            ->first();

        return view('livewire.dashboard-igd', [
            'rujukanBelumDitangani' => $rujukanBelumDitangani,
            'totalMenunggu' => (int) ($statistik->total ?? 0),
            'totalMerah' => (int) ($statistik->total_merah ?? 0),
            'totalKuning' => (int) ($statistik->total_kuning ?? 0),
            'totalHijau' => (int) ($statistik->total_hijau ?? 0),
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }

    /**
     * Determine the highest-priority zona from active alerts and dispatch the alarm.
     */
    private function bunyikanAlarmJikaAda(): void
    {
        if (! $this->alarmAktif || $this->alertAktif === []) {
            return;
        }

        $zona = 'kuning';
        foreach ($this->alertAktif as $alert) {
            if ($alert['zona'] === 'merah') {
                $zona = 'merah';
                break;
            }
        }

        $this->dispatch('bunyikan-alarm', zona: $zona);
    }
}
