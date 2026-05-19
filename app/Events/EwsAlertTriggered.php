<?php

namespace App\Events;

use App\Models\EwsAssessment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EwsAlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public EwsAssessment $assessment)
    {
        $this->assessment->loadMissing(['patient', 'faskes', 'petugas']);
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ews-alerts.rsud'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ews.alert';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->assessment->id,
            'nama_pasien' => $this->assessment->patient->nama_pasien,
            'no_rm' => $this->assessment->patient->no_rm,
            'faskes_asal' => $this->assessment->faskes->nama_faskes,
            'total_skor' => $this->assessment->total_skor,
            'zona' => $this->assessment->zona,
            'zona_label' => $this->assessment->zona_label,
            'waktu_penilaian' => $this->assessment->waktu_penilaian->format('d/m/Y H:i'),
            'waktu' => $this->assessment->waktu_penilaian->format('d/m/Y H:i'),
            'petugas' => $this->assessment->petugas->name,
            'catatan' => $this->assessment->catatan_rujukan,
            'respon_klinik' => $this->assessment->respon_klinik,
        ];
    }
}
