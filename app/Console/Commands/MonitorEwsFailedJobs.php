<?php

namespace App\Console\Commands;

use App\Models\EwsBroadcastFailure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorEwsFailedJobs extends Command
{
    protected $signature = 'ews:monitor-failed';

    protected $description = 'Cek failed jobs EWS dan tampilkan ringkasan';

    public function handle(): int
    {
        $failedCount = DB::table('failed_jobs')
            ->where('queue', 'like', 'ews-%')
            ->count();

        $broadcastFail = EwsBroadcastFailure::where('resolved', false)->count();

        $this->table(
            ['Kategori', 'Jumlah', 'Status'],
            [
                ['Failed Jobs (Queue)', $failedCount, $failedCount > 0 ? 'Perlu perhatian' : 'OK'],
                ['Broadcast Failures', $broadcastFail, $broadcastFail > 0 ? 'Kritis' : 'OK'],
            ],
        );

        if ($failedCount > 0) {
            $this->warn('Jalankan: php artisan queue:retry all');
        }

        if ($broadcastFail > 0) {
            $this->error('Ada '.$broadcastFail.' alert yang gagal terkirim ke IGD.');
            $this->line('Lihat tabel ews_broadcast_failures untuk detail.');
        }

        return self::SUCCESS;
    }
}
