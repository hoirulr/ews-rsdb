<?php

namespace App\Jobs;

use App\Models\EwsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CatatEwsLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 10;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly int $ewsAssessmentId,
        private readonly int $userId,
        private readonly string $aksi,
        private readonly string $status = 'sukses',
        private readonly ?string $keterangan = null,
        private readonly array $payload = [],
        private readonly ?string $ipAddress = null,
        private readonly ?string $userAgent = null,
    ) {}

    public function handle(): void
    {
        EwsLog::create([
            'ews_assessment_id' => $this->ewsAssessmentId,
            'user_id' => $this->userId,
            'aksi' => $this->aksi,
            'status' => $this->status,
            'keterangan' => $this->keterangan,
            'payload' => $this->payload ?: null,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CatatEwsLog gagal', [
            'assessment_id' => $this->ewsAssessmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
