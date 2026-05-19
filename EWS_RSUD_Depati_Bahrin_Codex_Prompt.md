# 🏥 EWS RSUD Depati Bahrin — Codex Build Prompt

> **Instruksi untuk Codex**: Baca seluruh dokumen ini, lalu generate semua file yang diperlukan secara berurutan sesuai struktur yang ditentukan. Jangan skip langkah apapun.

---

## 📋 METADATA PROYEK

| Item | Detail |
|------|--------|
| **Nama Aplikasi** | EWS RSUD Depati Bahrin |
| **Framework** | Laravel 13 + Livewire 3 |
| **Database** | MySQL 8 |
| **Realtime** | Laravel Reverb (WebSocket) |
| **CSS** | Tailwind CSS v4 |
| **Auth & RBAC** | Laravel Breeze + Spatie Laravel Permission |
| **Export** | Maatwebsite Excel + DomPDF |
| **PHP** | >= 8.3 |

---

## 🎯 KONTEKS APLIKASI

Aplikasi **Early Warning Score (EWS)** untuk RSUD Depati Bahrin yang digunakan untuk:

1. **Puskesmas / RS Perujuk** → menginput data vital sign pasien, sistem menghitung skor EWS otomatis, dan mengirim rujukan ke IGD RSUD Depati Bahrin.
2. **Admin RSUD / Dokter IGD** → menerima alert real-time dari pasien zona kuning/merah, tangani via dashboard.
3. **Admin Sistem** → kelola user, faskes, dan role permission.

### Standar EWS yang Digunakan: NEWS2 (Royal College of Physicians 2012)

**Tabel Parameter Fisiologis:**

| Parameter | Skor 3 | Skor 2 | Skor 1 | Skor 0 | Skor 1 | Skor 2 | Skor 3 |
|-----------|--------|--------|--------|--------|--------|--------|--------|
| Respirasi (RR) | ≤8 | — | 9–11 | 12–20 | — | 21–24 | ≥25 |
| Saturasi O2 (SpO2) | ≤91 | 92–93 | 94–95 | ≥96 | — | — | — |
| Oksigen Tambahan | — | Ya | — | Tidak | — | — | — |
| Suhu (°C) | <35.0 | — | 35.1–35.9 | 36.0–38.0 | 38.1–39.0 | — | ≥39.1 |
| TD Sistolik (mmHg) | ≤85 | 86–95 | 96–99 | 100–179 | 180–200 | 201–219 | ≥220 |
| Nadi (bpm) | ≤40 | — | 41–50 | 51–90 | 91–110 | 111–130 | ≥131 |
| Tingkat Kesadaran | — | — | — | A (Alert) | — | — | V/P/U |

**Zona EWS:**
- **Zona Hijau** (Skor 0): Monitoring rutin 3x/hari
- **Zona Hijau Rendah** (Skor 1–4): Monitoring tiap 4–6 jam
- **Zona Kuning / Medium** (Skor 5–6, atau 3 di satu parameter): Monitoring tiap 1 jam, hubungi dokter jaga
- **Zona Merah / Tinggi** (Skor ≥7): Monitoring kontinyu, tangani dalam 30 menit, potensi ICU

**Respon Klinik berdasarkan Nilai EWS:**
- **Skor 0**: Monitoring EWS rutin (RR, BP, HR, pulse rate, temp, SpO2, tingkat kesadaran). Catat RM06.
- **Skor 1–4 (Rendah)**: Tiap 4 jam, lapor dokter jaga, DJB verifikasi kondisi dalam <1 jam, re-asses setelah 4 jam.
- **Skor 5–6 atau 3 di satu parameter (Medium)**: Tiap 1 jam, hubungi DJB, DJB verifikasi dalam 5 menit, bubuhkan stempel khusus EWS RM04, monitoring monitor portable, re-asses tiap 30 menit.
- **Skor ≥7 (Tinggi)**: Monitoring kontinyu, tangani dalam 30 menit, DJB lapor DPJP/Bida 3x, dokter spesialis, informasikan kemungkinan pindah ICU, jika cardiac arrest → Code Blue.

---

## 🗂️ STRUKTUR DATABASE

### Buat semua migration berikut secara berurutan:

### 1. `create_faskes_table`
```php
Schema::create('faskes', function (Blueprint $table) {
    $table->id();
    $table->string('nama_faskes');
    $table->enum('tipe', ['rsud', 'puskesmas', 'rs_perujuk']);
    $table->string('kode_faskes')->unique();
    $table->string('alamat')->nullable();
    $table->string('no_telp')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 2. Modifikasi `users` table (dalam migration terpisah `add_faskes_to_users_table`)
```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('faskes_id')->nullable()->constrained('faskes')->nullOnDelete();
    $table->string('no_hp')->nullable();
    $table->boolean('is_active')->default(true);
});
```

### 3. `create_patients_table`
```php
Schema::create('patients', function (Blueprint $table) {
    $table->id();
    $table->string('nama_pasien');
    $table->string('no_rm')->unique();
    $table->date('tanggal_lahir');
    $table->enum('jenis_kelamin', ['L', 'P']);
    $table->string('alamat')->nullable();
    $table->string('no_telp')->nullable();
    $table->foreignId('faskes_asal_id')->constrained('faskes');
    $table->timestamps();
});
```

### 4. `create_ews_assessments_table`
```php
Schema::create('ews_assessments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id')->constrained('patients');
    $table->foreignId('faskes_id')->constrained('faskes');
    $table->foreignId('user_id')->constrained('users'); // petugas input
    $table->datetime('waktu_penilaian');
    
    // Parameter Vital Sign
    $table->unsignedTinyInteger('respirasi');        // nilai aktual
    $table->unsignedTinyInteger('saturasi_o2');      // nilai aktual %
    $table->boolean('oksigen_tambahan');             // true = Ya
    $table->decimal('suhu', 4, 1);                  // contoh: 36.5
    $table->unsignedSmallInteger('td_sistolik');     // mmHg
    $table->unsignedSmallInteger('nadi');            // bpm
    $table->enum('kesadaran', ['A', 'V', 'P', 'U']); // AVPU
    
    // Skor per parameter
    $table->tinyInteger('skor_respirasi')->default(0);
    $table->tinyInteger('skor_saturasi')->default(0);
    $table->tinyInteger('skor_oksigen')->default(0);
    $table->tinyInteger('skor_suhu')->default(0);
    $table->tinyInteger('skor_td')->default(0);
    $table->tinyInteger('skor_nadi')->default(0);
    $table->tinyInteger('skor_kesadaran')->default(0);
    $table->tinyInteger('total_skor')->default(0);
    
    // Zona
    $table->enum('zona', ['hijau', 'kuning', 'merah'])->default('hijau');
    
    // Rujukan
    $table->text('catatan_rujukan')->nullable();
    $table->text('tindakan_yang_diberikan')->nullable();
    $table->enum('status', ['menunggu', 'ditangani', 'selesai'])->default('menunggu');
    $table->foreignId('ditangani_oleh')->nullable()->constrained('users');
    $table->datetime('waktu_ditangani')->nullable();
    
    // Flag broadcast
    $table->boolean('sudah_broadcast')->default(false);
    $table->boolean('alert_aktif')->default(false);
    
    $table->timestamps();
    $table->softDeletes();
});
```

### 5. `create_ews_logs_table`
```php
Schema::create('ews_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ews_assessment_id')->constrained('ews_assessments');
    $table->foreignId('user_id')->constrained('users');
    $table->string('aksi'); // 'input', 'tangani', 'selesai', 'update'
    $table->text('keterangan')->nullable();
    $table->timestamps();
});
```

---

## 📦 COMPOSER DEPENDENCIES

Jalankan perintah berikut:

```bash
composer require spatie/laravel-permission
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require laravel/reverb
```

Publish dan setup:
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan install:broadcasting --reverb
php artisan migrate
```

---

## 🔐 ROLES & PERMISSIONS

### Buat Seeder: `database/seeders/RolePermissionSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // EWS
            'input-ews',
            'view-own-ews',
            'view-all-ews',
            // Pasien
            'input-patient',
            'view-own-patient',
            'view-all-patient',
            // Alert
            'receive-ews-alert',
            'tangani-alert',
            // Export
            'export-ews',
            // Admin
            'manage-user',
            'manage-faskes',
            'manage-role',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Roles
        $puskesmas = Role::firstOrCreate(['name' => 'puskesmas']);
        $puskesmas->syncPermissions([
            'input-ews',
            'view-own-ews',
            'input-patient',
            'view-own-patient',
        ]);

        $adminRsud = Role::firstOrCreate(['name' => 'admin_rsud']);
        $adminRsud->syncPermissions([
            'view-all-ews',
            'view-all-patient',
            'receive-ews-alert',
            'tangani-alert',
            'export-ews',
        ]);

        $adminSistem = Role::firstOrCreate(['name' => 'admin_sistem']);
        $adminSistem->syncPermissions(Permission::all());
    }
}
```

### Buat Seeder: `database/seeders/FakesUserSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Faskes;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FakesUserSeeder extends Seeder
{
    public function run(): void
    {
        // Faskes RSUD
        $rsud = Faskes::firstOrCreate(
            ['kode_faskes' => 'RSUD-DB-001'],
            [
                'nama_faskes' => 'RSUD Depati Bahrin',
                'tipe' => 'rsud',
                'alamat' => 'Jl. Depati Bahrin, Bangka Barat',
                'no_telp' => '(0716) 123456',
            ]
        );

        // Faskes Puskesmas contoh
        $pkm = Faskes::firstOrCreate(
            ['kode_faskes' => 'PKM-BB-001'],
            [
                'nama_faskes' => 'Puskesmas Belinyu',
                'tipe' => 'puskesmas',
                'alamat' => 'Jl. Raya Belinyu',
                'no_telp' => '(0716) 654321',
            ]
        );

        // Admin Sistem
        $adminSistem = User::firstOrCreate(
            ['email' => 'admin@rsud-depatibahrin.id'],
            [
                'name' => 'Administrator Sistem',
                'password' => Hash::make('password'),
                'faskes_id' => $rsud->id,
            ]
        );
        $adminSistem->assignRole('admin_sistem');

        // Admin RSUD / Dokter IGD
        $dokterIgd = User::firstOrCreate(
            ['email' => 'igd@rsud-depatibahrin.id'],
            [
                'name' => 'Dokter IGD RSUD',
                'password' => Hash::make('password'),
                'faskes_id' => $rsud->id,
            ]
        );
        $dokterIgd->assignRole('admin_rsud');

        // Petugas Puskesmas
        $perawat = User::firstOrCreate(
            ['email' => 'perawat@pkm-belinyu.id'],
            [
                'name' => 'Perawat Belinyu',
                'password' => Hash::make('password'),
                'faskes_id' => $pkm->id,
            ]
        );
        $perawat->assignRole('puskesmas');
    }
}
```

---

## 🧠 MODEL UTAMA

### `app/Models/Faskes.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faskes extends Model
{
    protected $fillable = [
        'nama_faskes', 'tipe', 'kode_faskes', 'alamat', 'no_telp', 'is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ewsAssessments(): HasMany
    {
        return $this->hasMany(EwsAssessment::class);
    }

    public function getTipeLabelAttribute(): string
    {
        return match($this->tipe) {
            'rsud' => 'RSUD',
            'puskesmas' => 'Puskesmas',
            'rs_perujuk' => 'RS Perujuk',
            default => 'Faskes',
        };
    }
}
```

### `app/Models/Patient.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'nama_pasien', 'no_rm', 'tanggal_lahir', 'jenis_kelamin',
        'alamat', 'no_telp', 'faskes_asal_id'
    ];

    protected $casts = ['tanggal_lahir' => 'date'];

    public function faskesAsal(): BelongsTo
    {
        return $this->belongsTo(Faskes::class, 'faskes_asal_id');
    }

    public function ewsAssessments(): HasMany
    {
        return $this->hasMany(EwsAssessment::class);
    }

    public function getUmurAttribute(): int
    {
        return $this->tanggal_lahir->age;
    }
}
```

### `app/Models/EwsAssessment.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EwsAssessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'faskes_id', 'user_id', 'waktu_penilaian',
        'respirasi', 'saturasi_o2', 'oksigen_tambahan',
        'suhu', 'td_sistolik', 'nadi', 'kesadaran',
        'skor_respirasi', 'skor_saturasi', 'skor_oksigen',
        'skor_suhu', 'skor_td', 'skor_nadi', 'skor_kesadaran',
        'total_skor', 'zona',
        'catatan_rujukan', 'tindakan_yang_diberikan',
        'status', 'ditangani_oleh', 'waktu_ditangani',
        'sudah_broadcast', 'alert_aktif',
    ];

    protected $casts = [
        'waktu_penilaian' => 'datetime',
        'waktu_ditangani' => 'datetime',
        'oksigen_tambahan' => 'boolean',
        'sudah_broadcast' => 'boolean',
        'alert_aktif' => 'boolean',
        'suhu' => 'decimal:1',
    ];

    // ===== RELASI =====
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function faskes(): BelongsTo
    {
        return $this->belongsTo(Faskes::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penangananOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }

    // ===== KALKULASI EWS =====

    public static function hitungSkorRespirasi(int $rr): int
    {
        return match(true) {
            $rr <= 8 => 3,
            $rr <= 11 => 1,
            $rr <= 20 => 0,
            $rr <= 24 => 2,
            default => 3,
        };
    }

    public static function hitungSkorSaturasi(int $spo2): int
    {
        return match(true) {
            $spo2 <= 91 => 3,
            $spo2 <= 93 => 2,
            $spo2 <= 95 => 1,
            default => 0,
        };
    }

    public static function hitungSkorOksigen(bool $oksigenTambahan): int
    {
        return $oksigenTambahan ? 2 : 0;
    }

    public static function hitungSkorSuhu(float $suhu): int
    {
        return match(true) {
            $suhu < 35.0 => 3,
            $suhu <= 35.9 => 1,
            $suhu <= 38.0 => 0,
            $suhu <= 39.0 => 1,
            default => 2,
        };
    }

    public static function hitungSkorTd(int $sistolik): int
    {
        return match(true) {
            $sistolik <= 85 => 3,
            $sistolik <= 95 => 2,
            $sistolik <= 99 => 1,
            $sistolik <= 179 => 0,
            $sistolik <= 200 => 1,
            $sistolik <= 219 => 2,
            default => 3,
        };
    }

    public static function hitungSkorNadi(int $nadi): int
    {
        return match(true) {
            $nadi <= 40 => 3,
            $nadi <= 50 => 1,
            $nadi <= 90 => 0,
            $nadi <= 110 => 1,
            $nadi <= 130 => 2,
            default => 3,
        };
    }

    public static function hitungSkorKesadaran(string $kesadaran): int
    {
        return $kesadaran === 'A' ? 0 : 3;
    }

    public static function tentukanZona(int $totalSkor, array $skorPerParameter): string
    {
        // Jika ada satu parameter skor 3 → minimal kuning
        $adaSkor3 = in_array(3, $skorPerParameter);

        if ($totalSkor >= 7) {
            return 'merah';
        }

        if ($totalSkor >= 5 || $adaSkor3) {
            return 'kuning';
        }

        return 'hijau';
    }

    public static function kalkulasiLengkap(array $data): array
    {
        $sRR  = self::hitungSkorRespirasi((int) $data['respirasi']);
        $sSpo = self::hitungSkorSaturasi((int) $data['saturasi_o2']);
        $sO2  = self::hitungSkorOksigen((bool) $data['oksigen_tambahan']);
        $sSuhu = self::hitungSkorSuhu((float) $data['suhu']);
        $sTD  = self::hitungSkorTd((int) $data['td_sistolik']);
        $sNadi = self::hitungSkorNadi((int) $data['nadi']);
        $sKes = self::hitungSkorKesadaran($data['kesadaran']);

        $total = $sRR + $sSpo + $sO2 + $sSuhu + $sTD + $sNadi + $sKes;
        $zona  = self::tentukanZona($total, [$sRR, $sSpo, $sO2, $sSuhu, $sTD, $sNadi, $sKes]);

        return [
            'skor_respirasi' => $sRR,
            'skor_saturasi'  => $sSpo,
            'skor_oksigen'   => $sO2,
            'skor_suhu'      => $sSuhu,
            'skor_td'        => $sTD,
            'skor_nadi'      => $sNadi,
            'skor_kesadaran' => $sKes,
            'total_skor'     => $total,
            'zona'           => $zona,
        ];
    }

    // ===== HELPER =====
    public function getZonaColorAttribute(): string
    {
        return match($this->zona) {
            'merah' => 'red',
            'kuning' => 'yellow',
            default => 'green',
        };
    }

    public function getZonaLabelAttribute(): string
    {
        return match($this->zona) {
            'merah' => 'ZONA MERAH - Gawat Darurat',
            'kuning' => 'ZONA KUNING - Waspada',
            default => 'ZONA HIJAU - Normal',
        };
    }

    public function getResponKlinikAttribute(): string
    {
        return match(true) {
            $this->total_skor >= 7 => 'Continuous monitoring dan penanganan dalam 30 menit. Hubungi DJB segera, lapor DPJP/Bida, pertimbangkan pindah ICU.',
            $this->total_skor >= 5 || $this->zona === 'kuning' => 'Monitoring tiap 1 jam. Hubungi DJB, verifikasi dalam 5 menit. Bubuhkan stempel EWS pada RM04.',
            $this->total_skor >= 1 => 'Monitoring tiap 4 jam. Lapor dokter jaga. DJB verifikasi kondisi pasien dalam <1 jam.',
            default => 'Monitoring EWS rutin 3x/hari (08.00, 14.00, 21.00). Catat pada RM06.',
        };
    }
}
```

---

## 📡 BROADCASTING EVENT

### `app/Events/EwsAlertTriggered.php`
```php
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
    }

    public function broadcastOn(): array
    {
        // Channel: ews-alerts.{faskes_id_rsud} → hanya admin RSUD Depati Bahrin
        return [
            new PrivateChannel('ews-alerts.rsud'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ews.alert';
    }

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
            'petugas' => $this->assessment->petugas->name,
            'catatan' => $this->assessment->catatan_rujukan,
            'respon_klinik' => $this->assessment->respon_klinik,
        ];
    }
}
```

### `routes/channels.php`
```php
<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ews-alerts.rsud', function ($user) {
    return $user->hasRole(['admin_rsud', 'admin_sistem']);
});
```

---

## ⚡ LIVEWIRE COMPONENTS

### `app/Livewire/FormRujukanEws.php`
```php
<?php

namespace App\Livewire;

use App\Events\EwsAlertTriggered;
use App\Models\EwsAssessment;
use App\Models\Patient;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class FormRujukanEws extends Component
{
    // Data Pasien
    public string $nama_pasien = '';
    public string $no_rm = '';
    public string $tanggal_lahir = '';
    public string $jenis_kelamin = 'L';

    // Waktu
    public string $waktu_penilaian = '';

    // Vital Sign
    public string $respirasi = '';
    public string $saturasi_o2 = '';
    public bool   $oksigen_tambahan = false;
    public string $suhu = '';
    public string $td_sistolik = '';
    public string $nadi = '';
    public string $kesadaran = 'A';

    // Hasil Kalkulasi (reaktif)
    public int    $total_skor = 0;
    public string $zona = '';
    public array  $skor_per_param = [];

    // Catatan
    public string $catatan_rujukan = '';
    public string $tindakan_yang_diberikan = '';

    // State
    public bool $sukses = false;
    public string $pesanSukses = '';

    public function mount(): void
    {
        $this->waktu_penilaian = now()->format('Y-m-d\TH:i');
    }

    // Dipanggil setiap kali ada perubahan input vital sign
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
        if (!$this->semuaVitalTerisi()) {
            return;
        }

        $hasil = EwsAssessment::kalkulasiLengkap([
            'respirasi'        => (int) $this->respirasi,
            'saturasi_o2'      => (int) $this->saturasi_o2,
            'oksigen_tambahan' => $this->oksigen_tambahan,
            'suhu'             => (float) $this->suhu,
            'td_sistolik'      => (int) $this->td_sistolik,
            'nadi'             => (int) $this->nadi,
            'kesadaran'        => $this->kesadaran,
        ]);

        $this->total_skor = $hasil['total_skor'];
        $this->zona = $hasil['zona'];
        $this->skor_per_param = $hasil;
    }

    private function semuaVitalTerisi(): bool
    {
        return $this->respirasi !== '' &&
               $this->saturasi_o2 !== '' &&
               $this->suhu !== '' &&
               $this->td_sistolik !== '' &&
               $this->nadi !== '';
    }

    public function kirimRujukan(): void
    {
        $this->validate([
            'nama_pasien'     => 'required|string|max:255',
            'no_rm'           => 'required|string|max:50',
            'tanggal_lahir'   => 'required|date',
            'jenis_kelamin'   => 'required|in:L,P',
            'waktu_penilaian' => 'required|date',
            'respirasi'       => 'required|integer|min:0|max:60',
            'saturasi_o2'     => 'required|integer|min:70|max:100',
            'suhu'            => 'required|numeric|min:30|max:45',
            'td_sistolik'     => 'required|integer|min:50|max:300',
            'nadi'            => 'required|integer|min:20|max:250',
            'kesadaran'       => 'required|in:A,V,P,U',
        ]);

        $user = Auth::user();

        // Cari atau buat data pasien
        $patient = Patient::firstOrCreate(
            ['no_rm' => $this->no_rm],
            [
                'nama_pasien'   => $this->nama_pasien,
                'tanggal_lahir' => $this->tanggal_lahir,
                'jenis_kelamin' => $this->jenis_kelamin,
                'faskes_asal_id' => $user->faskes_id,
            ]
        );

        $hasil = EwsAssessment::kalkulasiLengkap([
            'respirasi'        => (int) $this->respirasi,
            'saturasi_o2'      => (int) $this->saturasi_o2,
            'oksigen_tambahan' => $this->oksigen_tambahan,
            'suhu'             => (float) $this->suhu,
            'td_sistolik'      => (int) $this->td_sistolik,
            'nadi'             => (int) $this->nadi,
            'kesadaran'        => $this->kesadaran,
        ]);

        $perluBroadcast = in_array($hasil['zona'], ['kuning', 'merah']);

        $assessment = EwsAssessment::create([
            'patient_id'             => $patient->id,
            'faskes_id'              => $user->faskes_id,
            'user_id'                => $user->id,
            'waktu_penilaian'        => $this->waktu_penilaian,
            'respirasi'              => $this->respirasi,
            'saturasi_o2'            => $this->saturasi_o2,
            'oksigen_tambahan'       => $this->oksigen_tambahan,
            'suhu'                   => $this->suhu,
            'td_sistolik'            => $this->td_sistolik,
            'nadi'                   => $this->nadi,
            'kesadaran'              => $this->kesadaran,
            'catatan_rujukan'        => $this->catatan_rujukan,
            'tindakan_yang_diberikan' => $this->tindakan_yang_diberikan,
            'status'                 => 'menunggu',
            'alert_aktif'            => $perluBroadcast,
            ...$hasil,
        ]);

        // Broadcast jika zona kuning atau merah
        if ($perluBroadcast) {
            broadcast(new EwsAlertTriggered($assessment))->toOthers();

            $assessment->update(['sudah_broadcast' => true]);
        }

        $this->pesanSukses = 'Rujukan berhasil dikirim! Skor EWS: ' . $hasil['total_skor'] . ' (' . strtoupper($hasil['zona']) . ')';
        $this->sukses = true;

        $this->resetForm();
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
        return view('livewire.form-rujukan-ews')
            ->layout('layouts.app');
    }
}
```

### `app/Livewire/DashboardIgd.php`
```php
<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class DashboardIgd extends Component
{
    public array $alertAktif = [];
    public array $riwayatAlert = [];
    public bool  $alarmAktif = false;

    public function mount(): void
    {
        $this->muatAlert();
    }

    public function muatAlert(): void
    {
        $aktif = EwsAssessment::with(['patient', 'faskes', 'petugas'])
            ->where('alert_aktif', true)
            ->whereIn('zona', ['kuning', 'merah'])
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $this->alertAktif = $aktif->map(fn($a) => [
            'id'             => $a->id,
            'nama_pasien'    => $a->patient->nama_pasien,
            'no_rm'          => $a->patient->no_rm,
            'faskes_asal'    => $a->faskes->nama_faskes,
            'total_skor'     => $a->total_skor,
            'zona'           => $a->zona,
            'zona_label'     => $a->zona_label,
            'waktu'          => $a->waktu_penilaian->format('d/m/Y H:i'),
            'petugas'        => $a->petugas->name,
            'catatan'        => $a->catatan_rujukan,
            'respon_klinik'  => $a->respon_klinik,
        ])->toArray();

        $this->alarmAktif = count($this->alertAktif) > 0;
    }

    #[On('echo-private:ews-alerts.rsud,.ews.alert')]
    public function alertBaru(array $data): void
    {
        // Tambah ke array alert aktif (paling atas)
        array_unshift($this->alertAktif, $data);
        $this->alarmAktif = true;

        // Dispatch ke JS untuk bunyikan alarm
        $this->dispatch('bunyikan-alarm', zona: $data['zona']);
    }

    public function tanganiAlert(int $assessmentId): void
    {
        $assessment = EwsAssessment::findOrFail($assessmentId);
        $assessment->update([
            'status'          => 'ditangani',
            'alert_aktif'     => false,
            'ditangani_oleh'  => Auth::id(),
            'waktu_ditangani' => now(),
        ]);

        // Hapus dari array lokal
        $this->alertAktif = array_values(
            array_filter($this->alertAktif, fn($a) => $a['id'] !== $assessmentId)
        );

        if (empty($this->alertAktif)) {
            $this->alarmAktif = false;
            $this->dispatch('hentikan-alarm');
        }

        $this->dispatch('alert-ditangani', id: $assessmentId);
    }

    public function render()
    {
        return view('livewire.dashboard-igd')
            ->layout('layouts.app', ['title' => 'Dashboard IGD - EWS']);
    }
}
```

---

## 🎨 BLADE VIEWS

### `resources/views/layouts/app.blade.php`
```html
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'EWS RSUD Depati Bahrin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-50 font-sans antialiased">

    <!-- Navbar -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="font-bold text-lg">EWS RSUD Depati Bahrin</span>
                        <p class="text-blue-300 text-xs">Early Warning Score System</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-blue-200">{{ auth()->user()->name }}</span>
                    <span class="text-xs bg-blue-700 px-2 py-1 rounded-full">{{ auth()->user()->getRoleNames()->first() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-blue-300 hover:text-white transition">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar + Content -->
    <div class="flex h-[calc(100vh-4rem)]">
        <!-- Sidebar -->
        <aside class="w-64 bg-blue-800 text-white flex-shrink-0 overflow-y-auto">
            <nav class="p-4 space-y-1">
                @role('puskesmas|rs_perujuk|admin_sistem')
                <a href="{{ route('ews.form') }}"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition {{ request()->routeIs('ews.form') ? 'bg-blue-700' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Input Rujukan EWS</span>
                </a>
                <a href="{{ route('ews.riwayat') }}"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Riwayat Rujukan</span>
                </a>
                @endrole

                @role('admin_rsud|admin_sistem')
                <div class="pt-4 pb-1">
                    <p class="text-blue-400 text-xs uppercase font-semibold px-4">IGD RSUD</p>
                </div>
                <a href="{{ route('igd.dashboard') }}"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition {{ request()->routeIs('igd.dashboard') ? 'bg-blue-700' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                    </svg>
                    <span>Dashboard IGD</span>
                </a>
                <a href="{{ route('igd.monitoring') }}"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Monitoring Faskes</span>
                </a>
                @endrole

                @role('admin_sistem')
                <div class="pt-4 pb-1">
                    <p class="text-blue-400 text-xs uppercase font-semibold px-4">Admin Sistem</p>
                </div>
                <a href="{{ route('admin.user') }}"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                    </svg>
                    <span>Manajemen User</span>
                </a>
                <a href="{{ route('admin.faskes') }}"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Manajemen Faskes</span>
                </a>
                @endrole
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts

    <!-- Web Audio API untuk alarm -->
    <script>
    window.audioCtx = null;
    window.alarmInterval = null;
    window.alarmTimeout = null;

    function initAudio() {
        if (!window.audioCtx) {
            window.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
    }

    function bunyikanAlarm(zona) {
        initAudio();
        if (!window.audioCtx) return;

        const freq = zona === 'merah' ? 800 : 500;
        stopAlarm();

        function beep() {
            const osc = window.audioCtx.createOscillator();
            const gain = window.audioCtx.createGain();
            osc.connect(gain);
            gain.connect(window.audioCtx.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.5, window.audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, window.audioCtx.currentTime + 0.5);
            osc.start(window.audioCtx.currentTime);
            osc.stop(window.audioCtx.currentTime + 0.5);
        }

        beep();
        window.alarmInterval = setInterval(beep, 1000);

        // Stop otomatis setelah 30 detik
        window.alarmTimeout = setTimeout(stopAlarm, 30000);
    }

    function stopAlarm() {
        if (window.alarmInterval) clearInterval(window.alarmInterval);
        if (window.alarmTimeout) clearTimeout(window.alarmTimeout);
        window.alarmInterval = null;
        window.alarmTimeout = null;
    }

    // Livewire event listeners
    document.addEventListener('livewire:init', () => {
        Livewire.on('bunyikan-alarm', ({ zona }) => bunyikanAlarm(zona));
        Livewire.on('hentikan-alarm', () => stopAlarm());
    });
    </script>
</body>
</html>
```

### `resources/views/livewire/form-rujukan-ews.blade.php`
```html
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Form Rujukan EWS</h1>
        <p class="text-gray-500 text-sm mt-1">{{ auth()->user()->faskes->nama_faskes ?? '' }}</p>
    </div>

    @if($sukses)
    <div class="mb-4 p-4 rounded-lg bg-green-100 border border-green-400 text-green-800">
        ✅ {{ $pesanSukses }}
    </div>
    @endif

    <!-- VISUALISASI SKOR EWS -->
    @if($zona)
    <div class="mb-6 p-6 rounded-xl border-2 text-center
        @if($zona === 'merah') bg-red-50 border-red-400
        @elseif($zona === 'kuning') bg-yellow-50 border-yellow-400
        @else bg-green-50 border-green-400 @endif">
        <div class="text-6xl font-black
            @if($zona === 'merah') text-red-600
            @elseif($zona === 'kuning') text-yellow-600
            @else text-green-600 @endif">
            {{ $total_skor }}
        </div>
        <div class="text-lg font-bold mt-1
            @if($zona === 'merah') text-red-700
            @elseif($zona === 'kuning') text-yellow-700
            @else text-green-700 @endif">
            @if($zona === 'merah') 🔴 ZONA MERAH — Gawat Darurat
            @elseif($zona === 'kuning') 🟡 ZONA KUNING — Waspada
            @else 🟢 ZONA HIJAU — Normal
            @endif
        </div>
        <!-- Skor per parameter -->
        <div class="flex justify-center flex-wrap gap-2 mt-3">
            @foreach([
                'RR' => $skor_per_param['skor_respirasi'] ?? 0,
                'SpO2' => $skor_per_param['skor_saturasi'] ?? 0,
                'O2+' => $skor_per_param['skor_oksigen'] ?? 0,
                'Suhu' => $skor_per_param['skor_suhu'] ?? 0,
                'TD' => $skor_per_param['skor_td'] ?? 0,
                'Nadi' => $skor_per_param['skor_nadi'] ?? 0,
                'AVPU' => $skor_per_param['skor_kesadaran'] ?? 0,
            ] as $label => $skor)
            <span class="px-2 py-1 rounded text-xs font-bold
                @if($skor >= 3) bg-red-500 text-white
                @elseif($skor >= 2) bg-orange-400 text-white
                @elseif($skor >= 1) bg-yellow-300 text-gray-800
                @else bg-green-200 text-gray-800 @endif">
                {{ $label }}: {{ $skor }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    <form wire:submit.prevent="kirimRujukan" class="space-y-6">
        <!-- DATA PASIEN -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">📋 Data Pasien</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Nama Pasien *</label>
                    <input type="text" wire:model="nama_pasien" placeholder="Nama lengkap pasien"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('nama_pasien') border-red-400 @enderror">
                    @error('nama_pasien') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">No Rekam Medis *</label>
                    <input type="text" wire:model="no_rm" placeholder="Contoh: RM-2024-001"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('no_rm') border-red-400 @enderror">
                    @error('no_rm') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Lahir *</label>
                    <input type="date" wire:model="tanggal_lahir"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Jenis Kelamin *</label>
                    <select wire:model="jenis_kelamin"
                            class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Waktu Penilaian *</label>
                    <input type="datetime-local" wire:model="waktu_penilaian"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- VITAL SIGN -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">🩺 Vital Sign (Skor EWS dihitung otomatis)</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                <!-- Respirasi -->
                <div class="bg-gray-50 rounded-lg p-3 border">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Respirasi (RR)</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" wire:model.live="respirasi" min="1" max="60"
                               placeholder="12-20" class="w-full border rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <span class="text-xs text-gray-400">/min</span>
                    </div>
                    @if(isset($skor_per_param['skor_respirasi']))
                    <div class="mt-1 text-center">
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            @if($skor_per_param['skor_respirasi'] >= 3) bg-red-100 text-red-700
                            @elseif($skor_per_param['skor_respirasi'] >= 1) bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700 @endif">
                            Skor: {{ $skor_per_param['skor_respirasi'] }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Saturasi O2 -->
                <div class="bg-gray-50 rounded-lg p-3 border">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">SpO2</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" wire:model.live="saturasi_o2" min="70" max="100"
                               placeholder="≥96" class="w-full border rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <span class="text-xs text-gray-400">%</span>
                    </div>
                    @if(isset($skor_per_param['skor_saturasi']))
                    <div class="mt-1 text-center">
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            @if($skor_per_param['skor_saturasi'] >= 3) bg-red-100 text-red-700
                            @elseif($skor_per_param['skor_saturasi'] >= 1) bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700 @endif">
                            Skor: {{ $skor_per_param['skor_saturasi'] }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Oksigen Tambahan -->
                <div class="bg-gray-50 rounded-lg p-3 border">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">O2 Tambahan</label>
                    <div class="flex items-center mt-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="oksigen_tambahan"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                            <span class="text-sm">{{ $oksigen_tambahan ? 'Ya (NRM/RM)' : 'Tidak' }}</span>
                        </label>
                    </div>
                    @if(isset($skor_per_param['skor_oksigen']))
                    <div class="mt-1 text-center">
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            @if($skor_per_param['skor_oksigen'] >= 2) bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700 @endif">
                            Skor: {{ $skor_per_param['skor_oksigen'] }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Suhu -->
                <div class="bg-gray-50 rounded-lg p-3 border">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Suhu</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" wire:model.live="suhu" min="30" max="45" step="0.1"
                               placeholder="36.5" class="w-full border rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <span class="text-xs text-gray-400">°C</span>
                    </div>
                    @if(isset($skor_per_param['skor_suhu']))
                    <div class="mt-1 text-center">
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            @if($skor_per_param['skor_suhu'] >= 3) bg-red-100 text-red-700
                            @elseif($skor_per_param['skor_suhu'] >= 1) bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700 @endif">
                            Skor: {{ $skor_per_param['skor_suhu'] }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- TD Sistolik -->
                <div class="bg-gray-50 rounded-lg p-3 border">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">TD Sistolik</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" wire:model.live="td_sistolik" min="50" max="300"
                               placeholder="100-179" class="w-full border rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <span class="text-xs text-gray-400">mmHg</span>
                    </div>
                    @if(isset($skor_per_param['skor_td']))
                    <div class="mt-1 text-center">
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            @if($skor_per_param['skor_td'] >= 3) bg-red-100 text-red-700
                            @elseif($skor_per_param['skor_td'] >= 1) bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700 @endif">
                            Skor: {{ $skor_per_param['skor_td'] }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Nadi -->
                <div class="bg-gray-50 rounded-lg p-3 border">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nadi</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" wire:model.live="nadi" min="20" max="250"
                               placeholder="51-90" class="w-full border rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <span class="text-xs text-gray-400">bpm</span>
                    </div>
                    @if(isset($skor_per_param['skor_nadi']))
                    <div class="mt-1 text-center">
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            @if($skor_per_param['skor_nadi'] >= 3) bg-red-100 text-red-700
                            @elseif($skor_per_param['skor_nadi'] >= 1) bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700 @endif">
                            Skor: {{ $skor_per_param['skor_nadi'] }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Kesadaran AVPU -->
                <div class="bg-gray-50 rounded-lg p-3 border">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kesadaran AVPU</label>
                    <select wire:model.live="kesadaran"
                            class="w-full border rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="A">A - Alert</option>
                        <option value="V">V - Voice</option>
                        <option value="P">P - Pain</option>
                        <option value="U">U - Unresponsive</option>
                    </select>
                    @if(isset($skor_per_param['skor_kesadaran']))
                    <div class="mt-1 text-center">
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            @if($skor_per_param['skor_kesadaran'] >= 3) bg-red-100 text-red-700
                            @else bg-green-100 text-green-700 @endif">
                            Skor: {{ $skor_per_param['skor_kesadaran'] }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- CATATAN -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">📝 Catatan Rujukan</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Riwayat Singkat & Keluhan</label>
                    <textarea wire:model="catatan_rujukan" rows="3"
                              placeholder="Keluhan utama, riwayat penyakit, kondisi saat ini..."
                              class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tindakan yang Sudah Diberikan</label>
                    <textarea wire:model="tindakan_yang_diberikan" rows="2"
                              placeholder="Contoh: infus RL 500cc, O2 NRM 10 lpm, injeksi furosemide..."
                              class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
        </div>

        <!-- TOMBOL KIRIM -->
        <div class="flex justify-end">
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="px-8 py-3 rounded-xl font-semibold text-white transition
                        @if($zona === 'merah') bg-red-600 hover:bg-red-700
                        @elseif($zona === 'kuning') bg-yellow-500 hover:bg-yellow-600
                        @else bg-blue-600 hover:bg-blue-700 @endif">
                <span wire:loading.remove>
                    🚀 Kirim Rujukan
                    @if($zona === 'merah') (GAWAT DARURAT!)
                    @elseif($zona === 'kuning') (Waspada)
                    @endif
                </span>
                <span wire:loading>⏳ Mengirim...</span>
            </button>
        </div>
    </form>
</div>
```

### `resources/views/livewire/dashboard-igd.blade.php`
```html
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🏥 Dashboard IGD RSUD Depati Bahrin</h1>
            <p class="text-gray-500 text-sm">Real-time Early Warning Score Alert</p>
        </div>
        <div class="flex items-center space-x-3">
            @if($alarmAktif)
            <div class="flex items-center space-x-2 bg-red-100 text-red-700 px-4 py-2 rounded-full animate-pulse">
                <span class="text-sm font-bold">⚠️ {{ count($alertAktif) }} Alert Aktif</span>
            </div>
            @else
            <div class="flex items-center space-x-2 bg-green-100 text-green-700 px-4 py-2 rounded-full">
                <span class="text-sm font-bold">✅ Tidak Ada Alert</span>
            </div>
            @endif

            <button onclick="initAudio(); alert('Alarm diaktifkan!')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                🔔 Aktifkan Alarm
            </button>
        </div>
    </div>

    <!-- ALERT CARDS -->
    @forelse($alertAktif as $alert)
    <div class="mb-4 rounded-xl border-2 shadow-sm overflow-hidden
        @if($alert['zona'] === 'merah') border-red-400 bg-red-50
        @else border-yellow-400 bg-yellow-50 @endif">

        <!-- Header Alert Card -->
        <div class="px-6 py-3 flex items-center justify-between
            @if($alert['zona'] === 'merah') bg-red-600 text-white
            @else bg-yellow-500 text-white @endif">
            <div class="flex items-center space-x-3">
                <div class="text-3xl font-black">{{ $alert['total_skor'] }}</div>
                <div>
                    <div class="font-bold text-lg">{{ $alert['zona_label'] }}</div>
                    <div class="text-sm opacity-90">{{ $alert['faskes_asal'] }} • {{ $alert['waktu'] }}</div>
                </div>
            </div>
            <button wire:click="tanganiAlert({{ $alert['id'] }})"
                    wire:loading.attr="disabled"
                    class="px-5 py-2 bg-white font-bold rounded-lg transition
                        @if($alert['zona'] === 'merah') text-red-600 hover:bg-red-50
                        @else text-yellow-700 hover:bg-yellow-50 @endif">
                ✅ Tangani
            </button>
        </div>

        <!-- Body Alert Card -->
        <div class="px-6 py-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Nama Pasien</p>
                    <p class="font-semibold text-gray-800">{{ $alert['nama_pasien'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">No. RM</p>
                    <p class="font-semibold text-gray-800">{{ $alert['no_rm'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Diinput oleh</p>
                    <p class="font-semibold text-gray-800">{{ $alert['petugas'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Asal Faskes</p>
                    <p class="font-semibold text-gray-800">{{ $alert['faskes_asal'] }}</p>
                </div>
            </div>

            @if($alert['catatan'])
            <div class="mt-3 p-3 bg-white rounded-lg border">
                <p class="text-xs text-gray-500 mb-1">Catatan:</p>
                <p class="text-sm text-gray-700">{{ $alert['catatan'] }}</p>
            </div>
            @endif

            <div class="mt-3 p-3 rounded-lg
                @if($alert['zona'] === 'merah') bg-red-100 border border-red-200
                @else bg-yellow-100 border border-yellow-200 @endif">
                <p class="text-xs font-semibold uppercase mb-1
                    @if($alert['zona'] === 'merah') text-red-700
                    @else text-yellow-800 @endif">Respon Klinik:</p>
                <p class="text-sm
                    @if($alert['zona'] === 'merah') text-red-800
                    @else text-yellow-900 @endif">{{ $alert['respon_klinik'] }}</p>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-20 text-gray-400">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-lg font-medium">Tidak ada alert aktif saat ini</p>
        <p class="text-sm">Semua pasien dalam kondisi terpantau</p>
    </div>
    @endforelse
</div>
```

---

## 🌐 ROUTES

### `routes/web.php`
```php
<?php

use App\Livewire\FormRujukanEws;
use App\Livewire\DashboardIgd;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => redirect()->route('ews.form'))->name('dashboard');

    // Puskesmas / RS Perujuk
    Route::middleware(['role:puskesmas|rs_perujuk|admin_sistem'])->group(function () {
        Route::get('/ews/form', FormRujukanEws::class)->name('ews.form');
        Route::get('/ews/riwayat', \App\Livewire\RiwayatEws::class)->name('ews.riwayat');
    });

    // Admin RSUD / Dokter IGD
    Route::middleware(['role:admin_rsud|admin_sistem'])->group(function () {
        Route::get('/igd/dashboard', DashboardIgd::class)->name('igd.dashboard');
        Route::get('/igd/monitoring', \App\Livewire\MonitoringFaskes::class)->name('igd.monitoring');
    });

    // Admin Sistem
    Route::middleware(['role:admin_sistem'])->group(function () {
        Route::get('/admin/user', \App\Livewire\Admin\ManajemenUser::class)->name('admin.user');
        Route::get('/admin/faskes', \App\Livewire\Admin\ManajemenFaskes::class)->name('admin.faskes');
    });
});

require __DIR__.'/auth.php';
```

---

## ⚙️ KONFIGURASI

### `.env` (tambahkan/ubah bagian berikut)
```env
APP_NAME="EWS RSUD Depati Bahrin"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ews_rsud_depatibahrin
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=ews-rsud
REVERB_APP_KEY=ews-app-key-rsud
REVERB_APP_SECRET=ews-secret-rsud
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### `config/broadcasting.php` — pastikan default reverb aktif
```php
'default' => env('BROADCAST_CONNECTION', 'reverb'),
```

---

## 📱 JAVASCRIPT (Reverb + Livewire Echo)

### `resources/js/app.js`
```javascript
import './bootstrap';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

---

## 🚀 LANGKAH SETUP LENGKAP

```bash
# 1. Buat project Laravel baru
composer create-project laravel/laravel ews-rsud-depatibahrin

cd ews-rsud-depatibahrin

# 2. Install dependencies
composer require livewire/livewire
composer require spatie/laravel-permission
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf

# 3. Setup broadcasting (Reverb)
php artisan install:broadcasting

# 4. Install Laravel Breeze (auth)
composer require laravel/breeze --dev
php artisan breeze:install livewire

# 5. Setup NPM
npm install
npm install laravel-echo pusher-js

# 6. Publish Spatie
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# 7. Buat database MySQL
# CREATE DATABASE ews_rsud_depatibahrin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 8. Jalankan migration + seeder
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=FakesUserSeeder

# 9. Build assets
npm run build

# 10. Jalankan server (butuh 3 terminal)
php artisan serve                    # Terminal 1: Laravel server
php artisan reverb:start             # Terminal 2: WebSocket Reverb
npm run dev                          # Terminal 3: Vite (development)
```

---

## 🔧 PERINTAH ARTISAN UNTUK GENERATE KOMPONEN

```bash
# Buat Livewire components
php artisan make:livewire FormRujukanEws
php artisan make:livewire DashboardIgd
php artisan make:livewire RiwayatEws
php artisan make:livewire MonitoringFaskes
php artisan make:livewire Admin/ManajemenUser
php artisan make:livewire Admin/ManajemenFaskes

# Buat Events
php artisan make:event EwsAlertTriggered

# Buat Models
php artisan make:model Faskes -m
php artisan make:model Patient -m
php artisan make:model EwsAssessment -m
php artisan make:model EwsLog -m

# Buat Policies
php artisan make:policy EwsAssessmentPolicy --model=EwsAssessment
php artisan make:policy PatientPolicy --model=Patient
```

---

## 📊 FITUR TAMBAHAN (Setelah Inti Selesai)

### Export Excel (`app/Exports/EwsExport.php`)
```php
<?php

namespace App\Exports;

use App\Models\EwsAssessment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EwsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private ?string $faskesId = null,
        private ?string $zona = null,
        private ?string $dari = null,
        private ?string $sampai = null,
    ) {}

    public function query()
    {
        return EwsAssessment::with(['patient', 'faskes', 'petugas'])
            ->when($this->faskesId, fn($q) => $q->where('faskes_id', $this->faskesId))
            ->when($this->zona, fn($q) => $q->where('zona', $this->zona))
            ->when($this->dari, fn($q) => $q->whereDate('waktu_penilaian', '>=', $this->dari))
            ->when($this->sampai, fn($q) => $q->whereDate('waktu_penilaian', '<=', $this->sampai))
            ->orderByDesc('waktu_penilaian');
    }

    public function headings(): array
    {
        return [
            'No', 'Nama Pasien', 'No RM', 'Faskes Asal',
            'Waktu Penilaian', 'RR', 'SpO2', 'O2+',
            'Suhu', 'TD Sistolik', 'Nadi', 'Kesadaran',
            'Total Skor', 'Zona', 'Status', 'Petugas',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        return [
            $no,
            $row->patient->nama_pasien,
            $row->patient->no_rm,
            $row->faskes->nama_faskes,
            $row->waktu_penilaian->format('d/m/Y H:i'),
            $row->respirasi,
            $row->saturasi_o2 . '%',
            $row->oksigen_tambahan ? 'Ya' : 'Tidak',
            $row->suhu . '°C',
            $row->td_sistolik,
            $row->nadi,
            $row->kesadaran,
            $row->total_skor,
            strtoupper($row->zona),
            $row->status,
            $row->petugas->name,
        ];
    }
}
```

---

## 🗒️ CATATAN UNTUK CODEX

1. **Urutan eksekusi**: Buat migration → Model → Seeder → Event → Livewire → Views → Routes
2. **Jangan lupa**: Tambahkan `HasRoles` dari Spatie ke `User` model
3. **Pastikan**: `config/auth.php` guard web sudah benar
4. **Tailwind**: Gunakan `npm run dev` saat pengembangan, `npm run build` untuk produksi
5. **Reverb**: Harus dijalankan terpisah dengan `php artisan reverb:start`
6. **Channel auth**: Route `/broadcasting/auth` sudah disediakan Laravel Reverb secara otomatis
7. **Testing akun**: Login dengan `admin@rsud-depatibahrin.id` / `password` untuk admin sistem

---

*Dokumen ini mengacu pada standar NEWS2 Royal College of Physicians 2012.*  
*Versi: 1.0.0 — RSUD Depati Bahrin, Bangka Barat*
