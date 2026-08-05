<?php

namespace App\Jobs;

use App\Events\EwsAlertTriggered;
use App\Models\EwsAssessment;
use App\Models\EwsBroadcastFailure;
use App\Models\EwsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastEwsAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public function __construct(public readonly EwsAssessment $assessment) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('ews-alert-'.$this->assessment->id),
        ];
    }

    public function handle(): void
    {
        $assessment = EwsAssessment::with(['patient', 'faskes', 'petugas'])
            ->findOrFail($this->assessment->id);

        if (
            in_array($assessment->status, ['ditangani', 'selesai'], true)
            || ! in_array($assessment->zona, ['kuning', 'merah'], true)
        ) {
            Log::info('BroadcastEwsAlert: skipped', [
                'assessment_id' => $assessment->id,
                'reason' => 'status='.$assessment->status.', zona='.$assessment->zona,
            ]);

            return;
        }

        broadcast(new EwsAlertTriggered($assessment));

        $assessment->updateQuietly([
            'sudah_broadcast' => true,
            'alert_aktif' => true,
        ]);

        EwsLog::create([
            'ews_assessment_id' => $assessment->id,
            'user_id' => $assessment->user_id,
            'aksi' => 'broadcast',
            'status' => 'sukses',
            'keterangan' => 'Alert berhasil dikirim ke dashboard IGD via Reverb.',
            'payload' => [
                'zona' => $assessment->zona,
                'total_skor' => $assessment->total_skor,
                'attempt' => $this->attempts(),
            ],
        ]);

        Log::info('BroadcastEwsAlert: sukses', [
            'assessment_id' => $assessment->id,
            'zona' => $assessment->zona,
            'skor' => $assessment->total_skor,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BroadcastEwsAlert: gagal setelah '.$this->tries.' percobaan', [
            'assessment_id' => $this->assessment->id,
            'error' => $exception->getMessage(),
        ]);

        EwsBroadcastFailure::create([
            'ews_assessment_id' => $this->assessment->id,
            'attempt' => $this->tries,
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
        ]);

        EwsLog::create([
            'ews_assessment_id' => $this->assessment->id,
            'user_id' => $this->assessment->user_id,
            'aksi' => 'gagal_broadcast',
            'status' => 'gagal',
            'keterangan' => 'Broadcast gagal setelah '.$this->tries.' percobaan: '.$exception->getMessage(),
            'payload' => ['exception' => $exception::class],
        ]);

        // alert_aktif sengaja dibiarkan true: dashboard IGD punya fallback polling
        // berbasis kolom ini, sehingga alert tetap terlihat walau WebSocket down.
    }
}
