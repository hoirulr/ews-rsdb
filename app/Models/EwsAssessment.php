<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EwsAssessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'faskes_id',
        'user_id',
        'waktu_penilaian',
        'respirasi',
        'saturasi_o2',
        'oksigen_tambahan',
        'suhu',
        'td_sistolik',
        'nadi',
        'kesadaran',
        'skor_respirasi',
        'skor_saturasi',
        'skor_oksigen',
        'skor_suhu',
        'skor_td',
        'skor_nadi',
        'skor_kesadaran',
        'total_skor',
        'zona',
        'catatan_rujukan',
        'tindakan_yang_diberikan',
        'status',
        'ditangani_oleh',
        'waktu_ditangani',
        'feedback_hasil',
        'feedback_catatan',
        'feedback_oleh',
        'waktu_feedback',
        'sudah_broadcast',
        'alert_aktif',
    ];

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

    public function logs(): HasMany
    {
        return $this->hasMany(EwsLog::class);
    }

    public function broadcastFailures(): HasMany
    {
        return $this->hasMany(EwsBroadcastFailure::class);
    }

    public function feedbackOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'feedback_oleh');
    }

    public static function hitungSkorRespirasi(int $rr): int
    {
        return match (true) {
            $rr <= 8 => 3,
            $rr <= 11 => 1,
            $rr <= 20 => 0,
            $rr <= 24 => 2,
            default => 3,
        };
    }

    public static function hitungSkorSaturasi(int $spo2): int
    {
        return match (true) {
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
        return match (true) {
            $suhu <= 35.0 => 3,
            $suhu <= 36.0 => 1,
            $suhu <= 38.0 => 0,
            $suhu <= 39.0 => 1,
            default => 2,
        };
    }

    public static function hitungSkorTd(int $sistolik): int
    {
        return match (true) {
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
        return match (true) {
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

    /**
     * @param  array<int, int>  $skorPerParameter
     */
    public static function tentukanZona(int $totalSkor, array $skorPerParameter): string
    {
        $adaSkor3 = in_array(3, $skorPerParameter, true);

        if ($totalSkor >= 7) {
            return 'merah';
        }

        if ($totalSkor >= 5 || $adaSkor3) {
            return 'kuning';
        }

        return 'hijau';
    }

    /**
     * @param  array{respirasi:int|string,saturasi_o2:int|string,oksigen_tambahan:bool,suhu:float|string,td_sistolik:int|string,nadi:int|string,kesadaran:string}  $data
     * @return array{skor_respirasi:int,skor_saturasi:int,skor_oksigen:int,skor_suhu:int,skor_td:int,skor_nadi:int,skor_kesadaran:int,total_skor:int,zona:string}
     */
    public static function kalkulasiLengkap(array $data): array
    {
        $skorRespirasi = self::hitungSkorRespirasi((int) $data['respirasi']);
        $skorSaturasi = self::hitungSkorSaturasi((int) $data['saturasi_o2']);
        $skorOksigen = self::hitungSkorOksigen((bool) $data['oksigen_tambahan']);
        $skorSuhu = self::hitungSkorSuhu((float) $data['suhu']);
        $skorTd = self::hitungSkorTd((int) $data['td_sistolik']);
        $skorNadi = self::hitungSkorNadi((int) $data['nadi']);
        $skorKesadaran = self::hitungSkorKesadaran($data['kesadaran']);

        $totalSkor = $skorRespirasi + $skorSaturasi + $skorOksigen + $skorSuhu + $skorTd + $skorNadi + $skorKesadaran;

        return [
            'skor_respirasi' => $skorRespirasi,
            'skor_saturasi' => $skorSaturasi,
            'skor_oksigen' => $skorOksigen,
            'skor_suhu' => $skorSuhu,
            'skor_td' => $skorTd,
            'skor_nadi' => $skorNadi,
            'skor_kesadaran' => $skorKesadaran,
            'total_skor' => $totalSkor,
            'zona' => self::tentukanZona($totalSkor, [$skorRespirasi, $skorSaturasi, $skorOksigen, $skorSuhu, $skorTd, $skorNadi, $skorKesadaran]),
        ];
    }

    public function getZonaColorAttribute(): string
    {
        return match ($this->zona) {
            'merah' => 'red',
            'kuning' => 'yellow',
            default => 'green',
        };
    }

    public function getZonaLabelAttribute(): string
    {
        return match ($this->zona) {
            'merah' => 'ZONA MERAH - Gawat Darurat',
            'kuning' => 'ZONA KUNING - Waspada',
            default => 'ZONA HIJAU - Normal',
        };
    }

    public function getResponKlinikAttribute(): string
    {
        return match (true) {
            $this->total_skor >= 7 => 'Continuous monitoring dan penanganan dalam 30 menit. Hubungi DJB segera, lapor DPJP/Bida, pertimbangkan pindah ICU.',
            $this->total_skor >= 5 || $this->zona === 'kuning' => 'Monitoring tiap 1 jam. Hubungi DJB, verifikasi dalam 5 menit. Bubuhkan stempel EWS pada RM04.',
            $this->total_skor >= 1 => 'Monitoring tiap 4 jam. Lapor dokter jaga. DJB verifikasi kondisi pasien dalam <1 jam.',
            default => 'Monitoring EWS rutin 3x/hari (08.00, 14.00, 21.00). Catat pada RM06.',
        };
    }

    public function getFeedbackLabelAttribute(): ?string
    {
        return match ($this->feedback_hasil) {
            'meninggal' => 'Meninggal',
            'icu_lebih_24_jam' => 'Rawat Lebih dari 24 Jam di ICU',
            'rawat_inap_lebih_24_jam' => 'Rawat lebih dari 24 Jam di ruangan rawat inap',
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waktu_penilaian' => 'datetime',
            'waktu_ditangani' => 'datetime',
            'waktu_feedback' => 'datetime',
            'oksigen_tambahan' => 'boolean',
            'sudah_broadcast' => 'boolean',
            'alert_aktif' => 'boolean',
            'suhu' => 'decimal:1',
        ];
    }
}
