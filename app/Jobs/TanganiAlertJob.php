<?php

namespace App\Jobs;

use App\Models\EwsAssessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class TanganiAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $assessmentId,
        private readonly int $ditanganiOlehId,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('tangani-'.$this->assessmentId)];
    }

    public function handle(): void
    {
        $assessment = EwsAssessment::findOrFail($this->assessmentId);

        if ($assessment->status !== 'menunggu') {
            return;
        }

        $assessment->update([
            'status' => 'ditangani',
            'alert_aktif' => false,
            'ditangani_oleh' => $this->ditanganiOlehId,
            'waktu_ditangani' => now(),
        ]);

        CatatEwsLog::dispatch(
            ewsAssessmentId: $this->assessmentId,
            userId: $this->ditanganiOlehId,
            aksi: 'tangani',
            keterangan: 'Alert ditandai ditangani oleh dokter IGD.',
        )->onQueue('ews-log');
    }
}
