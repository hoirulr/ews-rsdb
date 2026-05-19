# ⚡ EWS — Queue Jobs + Database Transactions
## Suplemen untuk `EWS_RSUD_Depati_Bahrin_Codex_Prompt.md`

> **Instruksi Codex**: Dokumen ini adalah **tambahan wajib** dari prompt utama. Terapkan semua perubahan di bawah ini. Setiap bagian yang konflik dengan prompt utama → **dokumen ini yang berlaku**.

---

## 🧠 ARSITEKTUR: Kenapa Queue + Transaction?

```
Tanpa Queue (❌ Blocking):
  User klik "Kirim" → DB insert → broadcast Reverb → log → response
                        ←──── user menunggu semua selesai ────→

Dengan Queue (✅ Non-blocking):
  User klik "Kirim" → DB Transaction commit → response ke user (< 200ms)
                                ↓ (background, async)
                          Queue Worker:
                          ├─ BroadcastEwsAlert (dispatch event Reverb)
                          ├─ CatatEwsLog       (audit trail)
                          └─ NotifikasiPetugas (opsional: FCM, email)
```

**Aturan Penting:**
- **Di dalam Transaction**: hanya operasi DB yang kritikal (insert patient, insert assessment)
- **Di luar Transaction (via Queue)**: broadcast, logging, notifikasi eksternal
- **Jangan pernah** broadcast Reverb di dalam DB transaction — kalau transaction rollback, broadcast sudah terlanjur terkirim

---

## 📦 SETUP QUEUE

### 1. Migration: `create_jobs_table` + `create_failed_jobs_table`

```bash
php artisan queue:table
php artisan make:queue-failed-table
php artisan migrate
```

### 2. `.env` — konfigurasi queue

```env
# Gunakan database driver (cocok untuk RS tanpa Redis)
QUEUE_CONNECTION=database

# Jika RS punya Redis (lebih cepat, direkomendasikan produksi):
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
# REDIS_PORT=6379
```

### 3. `config/queue.php` — tambahkan named queues

```php
// Dalam array 'connections' → 'database':
'database' => [
    'driver'      => 'database',
    'connection'  => env('DB_QUEUE_CONNECTION'),
    'table'       => env('DB_QUEUE_TABLE', 'jobs'),
    'queue'       => env('DB_QUEUE', 'default'),
    'retry_after' => 90,
    'after_commit' => true, // ← PENTING: job hanya masuk queue SETELAH transaction commit
],
```

> **`after_commit: true`** adalah kunci keamanan: job tidak akan di-dispatch ke queue sampai DB transaction benar-benar berhasil commit. Kalau rollback → job tidak pernah masuk queue.

---

## 🗂️ MIGRATION TAMBAHAN

### `create_ews_logs_table` (versi lengkap dengan index)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ews_assessment_id')
                  ->constrained('ews_assessments')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained('users');
            $table->string('aksi', 50); // 'dibuat', 'broadcast', 'tangani', 'selesai', 'gagal_broadcast'
            $table->string('status', 20)->default('sukses'); // 'sukses', 'gagal', 'retry'
            $table->text('keterangan')->nullable();
            $table->json('payload')->nullable(); // data tambahan untuk debugging
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Index untuk query cepat
            $table->index(['ews_assessment_id', 'aksi']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_logs');
    }
};
```

### `create_ews_broadcast_failures_table` (track kegagalan broadcast)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_broadcast_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ews_assessment_id')->constrained('ews_assessments');
            $table->integer('attempt')->default(1);
            $table->text('error_message');
            $table->timestamp('failed_at');
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_broadcast_failures');
    }
};
```

---

## 💼 QUEUE JOBS

### `app/Jobs/BroadcastEwsAlert.php`

```php
<?php

namespace App\Jobs;

use App\Events\EwsAlertTriggered;
use App\Models\EwsAssessment;
use App\Models\EwsBroadcastFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class BroadcastEwsAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maksimum percobaan ulang jika gagal.
     * Untuk alert IGD: cukup 3x, setelah itu masuk failed_jobs.
     */
    public int $tries = 3;

    /**
     * Timeout per percobaan (detik).
     */
    public int $timeout = 30;

    /**
     * Backoff strategy: tunggu 5 detik, 15 detik, 30 detik sebelum retry.
     */
    public array $backoff = [5, 15, 30];

    /**
     * Queue khusus untuk alert IGD — priority tinggi.
     */
    public string $queue = 'ews-alert';

    public function __construct(
        public readonly EwsAssessment $assessment
    ) {}

    /**
     * Middleware: cegah dua job untuk assessment yang sama berjalan bersamaan.
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('ews-alert-' . $this->assessment->id),
        ];
    }

    public function handle(): void
    {
        // Re-fetch dari DB untuk memastikan data terbaru
        $assessment = EwsAssessment::with(['patient', 'faskes', 'petugas'])
            ->findOrFail($this->assessment->id);

        // Jangan broadcast jika sudah ditangani atau bukan zona alert
        if (
            $assessment->status === 'ditangani' ||
            $assessment->status === 'selesai' ||
            !in_array($assessment->zona, ['kuning', 'merah'])
        ) {
            Log::info('BroadcastEwsAlert: skipped', [
                'assessment_id' => $assessment->id,
                'reason' => 'status=' . $assessment->status . ', zona=' . $assessment->zona,
            ]);
            return;
        }

        // Broadcast event ke Reverb
        broadcast(new EwsAlertTriggered($assessment));

        // Update flag broadcast
        $assessment->updateQuietly([
            'sudah_broadcast' => true,
            'alert_aktif'     => true,
        ]);

        // Log sukses
        \App\Models\EwsLog::create([
            'ews_assessment_id' => $assessment->id,
            'user_id'           => $assessment->user_id,
            'aksi'              => 'broadcast',
            'status'            => 'sukses',
            'keterangan'        => 'Alert berhasil dikirim ke dashboard IGD via Reverb.',
            'payload'           => [
                'zona'        => $assessment->zona,
                'total_skor'  => $assessment->total_skor,
                'attempt'     => $this->attempts(),
            ],
        ]);

        Log::info('BroadcastEwsAlert: sukses', [
            'assessment_id' => $assessment->id,
            'zona'          => $assessment->zona,
            'skor'          => $assessment->total_skor,
        ]);
    }

    /**
     * Dipanggil ketika semua retry habis dan job tetap gagal.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BroadcastEwsAlert: GAGAL setelah ' . $this->tries . ' percobaan', [
            'assessment_id' => $this->assessment->id,
            'error'         => $exception->getMessage(),
        ]);

        // Catat ke tabel khusus
        \App\Models\EwsBroadcastFailure::create([
            'ews_assessment_id' => $this->assessment->id,
            'attempt'           => $this->tries,
            'error_message'     => $exception->getMessage(),
            'failed_at'         => now(),
        ]);

        // Log audit kegagalan
        \App\Models\EwsLog::create([
            'ews_assessment_id' => $this->assessment->id,
            'user_id'           => $this->assessment->user_id,
            'aksi'              => 'gagal_broadcast',
            'status'            => 'gagal',
            'keterangan'        => 'Broadcast gagal setelah ' . $this->tries . ' percobaan: ' . $exception->getMessage(),
            'payload'           => ['exception' => get_class($exception)],
        ]);

        // Tandai assessment perlu perhatian manual
        $this->assessment->updateQuietly(['alert_aktif' => false]);
    }
}
```

---

### `app/Jobs/CatatEwsLog.php`

```php
<?php

namespace App\Jobs;

use App\Models\EwsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CatatEwsLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 10;

    /**
     * Queue terpisah dari alert — prioritas lebih rendah.
     */
    public string $queue = 'ews-log';

    public function __construct(
        private readonly int    $ewsAssessmentId,
        private readonly int    $userId,
        private readonly string $aksi,
        private readonly string $status = 'sukses',
        private readonly ?string $keterangan = null,
        private readonly array  $payload = [],
        private readonly ?string $ipAddress = null,
        private readonly ?string $userAgent = null,
    ) {}

    public function handle(): void
    {
        EwsLog::create([
            'ews_assessment_id' => $this->ewsAssessmentId,
            'user_id'           => $this->userId,
            'aksi'              => $this->aksi,
            'status'            => $this->status,
            'keterangan'        => $this->keterangan,
            'payload'           => $this->payload ?: null,
            'ip_address'        => $this->ipAddress,
            'user_agent'        => $this->userAgent,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        // Log kegagalan pencatatan audit (worst case: tulis ke Laravel log)
        \Illuminate\Support\Facades\Log::error('CatatEwsLog gagal', [
            'assessment_id' => $this->ewsAssessmentId,
            'error'         => $e->getMessage(),
        ]);
    }
}
```

---

### `app/Jobs/TanganiAlertJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\EwsAssessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class TanganiAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public string $queue = 'ews-alert';

    public function __construct(
        private readonly int $assessmentId,
        private readonly int $ditanganiOlehId,
    ) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping('tangani-' . $this->assessmentId)];
    }

    public function handle(): void
    {
        $assessment = EwsAssessment::findOrFail($this->assessmentId);

        if ($assessment->status !== 'menunggu') {
            return; // Sudah ditangani oleh user lain, skip
        }

        $assessment->update([
            'status'          => 'ditangani',
            'alert_aktif'     => false,
            'ditangani_oleh'  => $this->ditanganiOlehId,
            'waktu_ditangani' => now(),
        ]);

        // Log tangani via queue terpisah
        CatatEwsLog::dispatch(
            ewsAssessmentId: $this->assessmentId,
            userId:          $this->ditanganiOlehId,
            aksi:            'tangani',
            keterangan:      'Alert ditandai ditangani oleh dokter IGD.',
        )->onQueue('ews-log');
    }
}
```

---

## 🔁 SERVICE CLASS — Orkestrasi Transaction + Queue

### `app/Services/EwsRujukanService.php`

```php
<?php

namespace App\Services;

use App\Jobs\BroadcastEwsAlert;
use App\Jobs\CatatEwsLog;
use App\Models\EwsAssessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EwsRujukanService
{
    /**
     * Proses pengiriman rujukan EWS secara aman.
     *
     * Alur:
     * 1. DB::transaction → insert patient + assessment (atomic)
     * 2. Setelah commit → dispatch queue jobs (non-blocking)
     *
     * @throws \Throwable
     */
    public function kirimRujukan(array $data, User $petugas): EwsAssessment
    {
        // ─── HITUNG SKOR (di luar transaction, murni CPU) ───────────────
        $hasil = EwsAssessment::kalkulasiLengkap([
            'respirasi'        => (int)   $data['respirasi'],
            'saturasi_o2'      => (int)   $data['saturasi_o2'],
            'oksigen_tambahan' => (bool)  $data['oksigen_tambahan'],
            'suhu'             => (float) $data['suhu'],
            'td_sistolik'      => (int)   $data['td_sistolik'],
            'nadi'             => (int)   $data['nadi'],
            'kesadaran'        =>         $data['kesadaran'],
        ]);

        $perluBroadcast = in_array($hasil['zona'], ['kuning', 'merah']);

        // ─── DB TRANSACTION ─────────────────────────────────────────────
        // Hanya operasi DB kritikal yang masuk sini.
        // Jika satu langkah gagal → semua rollback → tidak ada data setengah-jadi.
        $assessment = DB::transaction(function () use ($data, $petugas, $hasil, $perluBroadcast) {

            // 1. Upsert data pasien
            $patient = Patient::firstOrCreate(
                ['no_rm' => $data['no_rm']],
                [
                    'nama_pasien'    => $data['nama_pasien'],
                    'tanggal_lahir'  => $data['tanggal_lahir'],
                    'jenis_kelamin'  => $data['jenis_kelamin'],
                    'faskes_asal_id' => $petugas->faskes_id,
                ]
            );

            // 2. Simpan assessment EWS
            $assessment = EwsAssessment::create([
                'patient_id'              => $patient->id,
                'faskes_id'               => $petugas->faskes_id,
                'user_id'                 => $petugas->id,
                'waktu_penilaian'         => $data['waktu_penilaian'],
                'respirasi'               => $data['respirasi'],
                'saturasi_o2'             => $data['saturasi_o2'],
                'oksigen_tambahan'        => $data['oksigen_tambahan'],
                'suhu'                    => $data['suhu'],
                'td_sistolik'             => $data['td_sistolik'],
                'nadi'                    => $data['nadi'],
                'kesadaran'               => $data['kesadaran'],
                'catatan_rujukan'         => $data['catatan_rujukan'] ?? null,
                'tindakan_yang_diberikan' => $data['tindakan_yang_diberikan'] ?? null,
                'status'                  => 'menunggu',
                'alert_aktif'             => $perluBroadcast,
                'sudah_broadcast'         => false,
                ...$hasil,
            ]);

            return $assessment;

            // ← Setelah return: Laravel commit transaction.
            //   Karena `after_commit: true` di config queue,
            //   job hanya masuk queue SETELAH commit berhasil.
        });

        // ─── DISPATCH JOBS (after commit, non-blocking) ──────────────────
        if ($perluBroadcast) {
            // Job prioritas tinggi: kirim alert ke IGD
            BroadcastEwsAlert::dispatch($assessment)
                ->onQueue('ews-alert');
        }

        // Job prioritas rendah: audit trail
        CatatEwsLog::dispatch(
            ewsAssessmentId: $assessment->id,
            userId:          $petugas->id,
            aksi:            'dibuat',
            keterangan:      sprintf(
                'Rujukan baru dikirim. Skor: %d, Zona: %s. Broadcast: %s.',
                $hasil['total_skor'],
                strtoupper($hasil['zona']),
                $perluBroadcast ? 'Ya' : 'Tidak'
            ),
            payload:         [
                'skor_per_param' => array_intersect_key(
                    $hasil,
                    array_flip([
                        'skor_respirasi', 'skor_saturasi', 'skor_oksigen',
                        'skor_suhu', 'skor_td', 'skor_nadi', 'skor_kesadaran',
                    ])
                ),
                'ip'             => request()->ip(),
            ],
            ipAddress:       request()->ip(),
            userAgent:       request()->userAgent(),
        )->onQueue('ews-log');

        Log::info('EwsRujukanService: rujukan berhasil disimpan', [
            'assessment_id' => $assessment->id,
            'zona'          => $hasil['zona'],
            'perlu_broadcast' => $perluBroadcast,
        ]);

        return $assessment;
    }

    /**
     * Proses penanganan alert oleh dokter IGD.
     * Juga menggunakan transaction untuk menghindari race condition
     * jika dua dokter klik "Tangani" bersamaan.
     *
     * @throws \Throwable
     */
    public function tanganiAlert(int $assessmentId, User $dokter): EwsAssessment
    {
        $assessment = DB::transaction(function () use ($assessmentId, $dokter) {

            // Lock row untuk cegah race condition (dua dokter tangani bersamaan)
            $assessment = EwsAssessment::lockForUpdate()->findOrFail($assessmentId);

            if ($assessment->status !== 'menunggu') {
                // Sudah ditangani user lain — lempar exception, transaction rollback
                throw new \RuntimeException(
                    'Alert ini sudah ditangani oleh ' . ($assessment->penangananOleh?->name ?? 'petugas lain') . '.'
                );
            }

            $assessment->update([
                'status'          => 'ditangani',
                'alert_aktif'     => false,
                'ditangani_oleh'  => $dokter->id,
                'waktu_ditangani' => now(),
            ]);

            return $assessment;
        });

        // Log setelah commit
        CatatEwsLog::dispatch(
            ewsAssessmentId: $assessment->id,
            userId:          $dokter->id,
            aksi:            'tangani',
            keterangan:      'Alert ditangani oleh dokter IGD.',
            ipAddress:       request()->ip(),
        )->onQueue('ews-log');

        return $assessment;
    }
}
```

---

## ♻️ UPDATE LIVEWIRE COMPONENTS

### `app/Livewire/FormRujukanEws.php` — Versi dengan Service + Exception Handling

```php
<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use App\Services\EwsRujukanService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class FormRujukanEws extends Component
{
    // ── Properties (sama seperti sebelumnya) ──
    public string $nama_pasien = '';
    public string $no_rm = '';
    public string $tanggal_lahir = '';
    public string $jenis_kelamin = 'L';
    public string $waktu_penilaian = '';
    public string $respirasi = '';
    public string $saturasi_o2 = '';
    public bool   $oksigen_tambahan = false;
    public string $suhu = '';
    public string $td_sistolik = '';
    public string $nadi = '';
    public string $kesadaran = 'A';
    public int    $total_skor = 0;
    public string $zona = '';
    public array  $skor_per_param = [];
    public string $catatan_rujukan = '';
    public string $tindakan_yang_diberikan = '';
    public bool   $sukses = false;
    public string $pesanSukses = '';
    public string $pesanError = '';
    public bool   $sedangMengirim = false;

    public function mount(): void
    {
        $this->waktu_penilaian = now()->format('Y-m-d\TH:i');
    }

    public function updated(string $propertyName): void
    {
        $vitalFields = [
            'respirasi', 'saturasi_o2', 'oksigen_tambahan',
            'suhu', 'td_sistolik', 'nadi', 'kesadaran'
        ];

        if (in_array($propertyName, $vitalFields)) {
            $this->hitungSkor();
        }
    }

    public function hitungSkor(): void
    {
        if (!$this->semuaVitalTerisi()) return;

        $hasil = EwsAssessment::kalkulasiLengkap([
            'respirasi'        => (int)   $this->respirasi,
            'saturasi_o2'      => (int)   $this->saturasi_o2,
            'oksigen_tambahan' => $this->oksigen_tambahan,
            'suhu'             => (float) $this->suhu,
            'td_sistolik'      => (int)   $this->td_sistolik,
            'nadi'             => (int)   $this->nadi,
            'kesadaran'        => $this->kesadaran,
        ]);

        $this->total_skor    = $hasil['total_skor'];
        $this->zona          = $hasil['zona'];
        $this->skor_per_param = $hasil;
    }

    public function kirimRujukan(EwsRujukanService $service): void
    {
        // Reset state
        $this->pesanError  = '';
        $this->sukses      = false;
        $this->sedangMengirim = true;

        $this->validate([
            'nama_pasien'     => 'required|string|max:255',
            'no_rm'           => 'required|string|max:50',
            'tanggal_lahir'   => 'required|date|before:today',
            'jenis_kelamin'   => 'required|in:L,P',
            'waktu_penilaian' => 'required|date',
            'respirasi'       => 'required|integer|min:1|max:60',
            'saturasi_o2'     => 'required|integer|min:70|max:100',
            'suhu'            => 'required|numeric|min:30.0|max:45.0',
            'td_sistolik'     => 'required|integer|min:50|max:300',
            'nadi'            => 'required|integer|min:20|max:250',
            'kesadaran'       => 'required|in:A,V,P,U',
        ]);

        try {
            $assessment = $service->kirimRujukan(
                data: [
                    'nama_pasien'             => $this->nama_pasien,
                    'no_rm'                   => $this->no_rm,
                    'tanggal_lahir'           => $this->tanggal_lahir,
                    'jenis_kelamin'           => $this->jenis_kelamin,
                    'waktu_penilaian'         => $this->waktu_penilaian,
                    'respirasi'               => $this->respirasi,
                    'saturasi_o2'             => $this->saturasi_o2,
                    'oksigen_tambahan'        => $this->oksigen_tambahan,
                    'suhu'                    => $this->suhu,
                    'td_sistolik'             => $this->td_sistolik,
                    'nadi'                    => $this->nadi,
                    'kesadaran'               => $this->kesadaran,
                    'catatan_rujukan'         => $this->catatan_rujukan,
                    'tindakan_yang_diberikan' => $this->tindakan_yang_diberikan,
                ],
                petugas: Auth::user()
            );

            $zonaLabel = match($assessment->zona) {
                'merah'  => '🔴 ZONA MERAH — Gawat Darurat!',
                'kuning' => '🟡 ZONA KUNING — Waspada',
                default  => '🟢 ZONA HIJAU — Normal',
            };

            $this->pesanSukses = sprintf(
                'Rujukan berhasil dikirim! Skor EWS: %d (%s). %s',
                $assessment->total_skor,
                $zonaLabel,
                in_array($assessment->zona, ['kuning', 'merah'])
                    ? 'Alert sedang dikirim ke IGD RSUD.'
                    : ''
            );

            $this->sukses = true;
            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Biarkan Livewire handle validation error

        } catch (\Throwable $e) {
            $this->pesanError = 'Gagal mengirim rujukan. Silakan coba lagi atau hubungi admin. (' . $e->getMessage() . ')';

            \Illuminate\Support\Facades\Log::error('FormRujukanEws: gagal', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

        } finally {
            $this->sedangMengirim = false;
        }
    }

    private function semuaVitalTerisi(): bool
    {
        return $this->respirasi !== '' && $this->saturasi_o2 !== '' &&
               $this->suhu !== '' && $this->td_sistolik !== '' && $this->nadi !== '';
    }

    private function resetForm(): void
    {
        $this->nama_pasien = '';
        $this->no_rm = '';
        $this->tanggal_lahir = '';
        $this->jenis_kelamin = 'L';
        $this->waktu_penilaian = now()->format('Y-m-d\TH:i');
        $this->respirasi = '';
        $this->saturasi_o2 = '';
        $this->oksigen_tambahan = false;
        $this->suhu = '';
        $this->td_sistolik = '';
        $this->nadi = '';
        $this->kesadaran = 'A';
        $this->total_skor = 0;
        $this->zona = '';
        $this->skor_per_param = [];
        $this->catatan_rujukan = '';
        $this->tindakan_yang_diberikan = '';
    }

    public function render()
    {
        return view('livewire.form-rujukan-ews')->layout('layouts.app');
    }
}
```

### Update `app/Livewire/DashboardIgd.php` — Gunakan Service

```php
// Ganti method tanganiAlert() dengan versi ini:

public function tanganiAlert(int $assessmentId, EwsRujukanService $service): void
{
    try {
        $service->tanganiAlert($assessmentId, Auth::user());

        // Update array lokal
        $this->alertAktif = array_values(
            array_filter($this->alertAktif, fn($a) => $a['id'] !== $assessmentId)
        );

        if (empty($this->alertAktif)) {
            $this->alarmAktif = false;
            $this->dispatch('hentikan-alarm');
        }

        $this->dispatch('alert-ditangani', id: $assessmentId);

    } catch (\RuntimeException $e) {
        // Race condition: alert sudah ditangani dokter lain
        $this->dispatch('notification', [
            'type'    => 'warning',
            'message' => $e->getMessage(),
        ]);

        // Refresh data dari DB
        $this->muatAlert();
    }
}
```

---

## 🔧 MODEL TAMBAHAN

### `app/Models/EwsLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwsLog extends Model
{
    protected $fillable = [
        'ews_assessment_id', 'user_id', 'aksi', 'status',
        'keterangan', 'payload', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(EwsAssessment::class, 'ews_assessment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### `app/Models/EwsBroadcastFailure.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwsBroadcastFailure extends Model
{
    protected $fillable = [
        'ews_assessment_id', 'attempt', 'error_message', 'failed_at', 'resolved',
    ];

    protected $casts = [
        'failed_at' => 'datetime',
        'resolved'  => 'boolean',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(EwsAssessment::class, 'ews_assessment_id');
    }
}
```

---

## 📋 SUPERVISOR CONFIG (Produksi Linux)

Supervisor memastikan queue worker terus berjalan walau crash.

### `/etc/supervisor/conf.d/ews-worker.conf`

```ini
; Worker untuk alert IGD — prioritas tinggi, 4 proses paralel
[program:ews-alert-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ews-rsud/artisan queue:work database --queue=ews-alert --sleep=1 --tries=3 --timeout=30 --max-jobs=500 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/ews-alert-worker.log
stopwaitsecs=60

; Worker untuk audit log — prioritas rendah, 2 proses
[program:ews-log-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ews-rsud/artisan queue:work database --queue=ews-log --sleep=3 --tries=5 --timeout=10 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/ews-log-worker.log
stopwaitsecs=30

; Worker default (fallback)
[program:ews-default-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ews-rsud/artisan queue:work database --queue=default --sleep=5 --tries=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/ews-default-worker.log
```

```bash
# Reload supervisor setelah edit config
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all

# Cek status
sudo supervisorctl status
```

---

## 🛡️ FAILED JOB MONITORING

### `app/Console/Commands/MonitorEwsFailedJobs.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\EwsBroadcastFailure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorEwsFailedJobs extends Command
{
    protected $signature   = 'ews:monitor-failed';
    protected $description = 'Cek failed jobs EWS dan tampilkan ringkasan';

    public function handle(): void
    {
        // Failed jobs dari Laravel default
        $failedCount = DB::table('failed_jobs')
            ->where('queue', 'like', 'ews-%')
            ->count();

        // Broadcast failures custom
        $broadcastFail = EwsBroadcastFailure::where('resolved', false)->count();

        $this->table(
            ['Kategori', 'Jumlah', 'Status'],
            [
                ['Failed Jobs (Queue)', $failedCount, $failedCount > 0 ? '⚠️ Perlu perhatian' : '✅ OK'],
                ['Broadcast Failures',  $broadcastFail, $broadcastFail > 0 ? '🔴 Kritis' : '✅ OK'],
            ]
        );

        if ($failedCount > 0) {
            $this->warn('Jalankan: php artisan queue:retry all');
        }

        if ($broadcastFail > 0) {
            $this->error('Ada ' . $broadcastFail . ' alert yang gagal terkirim ke IGD!');
            $this->line('Lihat tabel ews_broadcast_failures untuk detail.');
        }
    }
}
```

### `routes/console.php` — Schedule monitoring

```php
<?php

use Illuminate\Support\Facades\Schedule;

// Cek failed jobs setiap 5 menit
Schedule::command('ews:monitor-failed')
    ->everyFiveMinutes()
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@rsud-depatibahrin.id'));

// Bersihkan failed jobs lama (>7 hari) setiap malam
Schedule::command('queue:flush')
    ->daily()
    ->at('02:00');

// Retry failed jobs EWS otomatis setiap 15 menit (hati-hati, pastikan aman)
// Schedule::command('queue:retry all')->everyFifteenMinutes();
```

```bash
# Jalankan scheduler (tambahkan ke crontab server)
# crontab -e
* * * * * cd /var/www/ews-rsud && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 PERINTAH ARTISAN PENTING

```bash
# Generate semua file baru
php artisan make:job BroadcastEwsAlert
php artisan make:job CatatEwsLog
php artisan make:job TanganiAlertJob
php artisan make:service EwsRujukanService   # (buat manual di app/Services/)
php artisan make:model EwsLog -m
php artisan make:model EwsBroadcastFailure -m
php artisan make:command MonitorEwsFailedJobs

# Migration
php artisan migrate

# Jalankan worker development (semua queue sekaligus)
php artisan queue:work database --queue=ews-alert,ews-log,default --tries=3

# Monitor queue secara real-time
php artisan queue:monitor ews-alert,ews-log

# Lihat failed jobs
php artisan queue:failed

# Retry semua failed jobs
php artisan queue:retry all

# Retry job spesifik (gunakan ID dari queue:failed)
php artisan queue:retry {id}

# Hapus semua failed jobs
php artisan queue:flush

# Cek custom monitor
php artisan ews:monitor-failed
```

---

## ✅ CHECKLIST VERIFIKASI

Setelah implementasi selesai, verifikasi dengan urutan ini:

```
[ ] config/queue.php → after_commit: true sudah diset
[ ] .env → QUEUE_CONNECTION=database
[ ] Migration ews_logs + ews_broadcast_failures sudah jalan
[ ] BroadcastEwsAlert menggunakan onQueue('ews-alert')
[ ] CatatEwsLog menggunakan onQueue('ews-log')
[ ] EwsRujukanService::kirimRujukan() menggunakan DB::transaction()
[ ] EwsRujukanService::tanganiAlert() menggunakan lockForUpdate()
[ ] FormRujukanEws menggunakan EwsRujukanService (bukan langsung DB)
[ ] Queue worker jalan: php artisan queue:work database --queue=ews-alert,ews-log
[ ] Test: submit form → response cepat (< 300ms) → alert muncul di IGD beberapa detik kemudian
[ ] Test rollback: matikan MySQL saat submit → tidak ada data setengah-jadi
[ ] Test race condition: dua tab browser klik Tangani bersamaan → hanya satu berhasil
```

---

## 📐 RINGKASAN ARSITEKTUR FINAL

```
FormRujukanEws (Livewire)
        │
        ▼
EwsRujukanService::kirimRujukan()
        │
        ├─ [Hitung Skor EWS] ──────────────────── CPU only, no DB
        │
        ├─ DB::transaction() ─────────────────── ATOMIC
        │   ├─ Patient::firstOrCreate()
        │   └─ EwsAssessment::create()
        │   └─ [COMMIT] ◄─ user dapat response di sini (< 200ms)
        │
        └─ after_commit → dispatch jobs ke Queue:
            ├─ BroadcastEwsAlert  [queue: ews-alert]  → tries:3, backoff:5/15/30s
            │       └─ broadcast(EwsAlertTriggered) → Reverb → DashboardIgd
            │
            └─ CatatEwsLog        [queue: ews-log]   → tries:5
                    └─ EwsLog::create() → audit trail

Supervisor: 4 worker ews-alert + 2 worker ews-log (always running)
```

---

*Suplemen ini wajib digabung dengan `EWS_RSUD_Depati_Bahrin_Codex_Prompt.md`.*  
*Versi: 1.1.0 — Queue + Transaction Layer*
