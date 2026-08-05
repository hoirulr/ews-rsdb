<?php

namespace App\Services;

use App\Jobs\BroadcastEwsAlert;
use App\Jobs\CatatEwsLog;
use App\Models\EwsAssessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EwsRujukanService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Throwable
     */
    public function kirimRujukan(array $data, User $petugas): EwsAssessment
    {
        if ($petugas->faskes_id === null) {
            throw new RuntimeException('User belum terhubung ke faskes.');
        }

        $hasil = EwsAssessment::kalkulasiLengkap([
            'respirasi' => (int) $data['respirasi'],
            'saturasi_o2' => (int) $data['saturasi_o2'],
            'oksigen_tambahan' => (bool) $data['oksigen_tambahan'],
            'suhu' => (float) $data['suhu'],
            'td_sistolik' => (int) $data['td_sistolik'],
            'nadi' => (int) $data['nadi'],
            'kesadaran' => (string) $data['kesadaran'],
        ]);

        $perluBroadcast = in_array($hasil['zona'], ['kuning', 'merah'], true);

        $assessment = DB::transaction(function () use ($data, $petugas, $hasil, $perluBroadcast): EwsAssessment {
            // No RM adalah penomoran internal faskes, jadi pencarian pasien
            // harus dibatasi ke faskes perujuk agar pasien antar-faskes yang
            // kebetulan punya no RM sama tidak tergabung.
            $patient = Patient::firstOrCreate(
                [
                    'no_rm' => $data['no_rm'],
                    'faskes_asal_id' => $petugas->faskes_id,
                ],
                [
                    'nama_pasien' => $data['nama_pasien'],
                    'tanggal_lahir' => $data['tanggal_lahir'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                ],
            );

            return EwsAssessment::create([
                'patient_id' => $patient->id,
                'faskes_id' => $petugas->faskes_id,
                'user_id' => $petugas->id,
                'waktu_penilaian' => $data['waktu_penilaian'],
                'respirasi' => (int) $data['respirasi'],
                'saturasi_o2' => (int) $data['saturasi_o2'],
                'oksigen_tambahan' => (bool) $data['oksigen_tambahan'],
                'suhu' => (float) $data['suhu'],
                'td_sistolik' => (int) $data['td_sistolik'],
                'nadi' => (int) $data['nadi'],
                'kesadaran' => $data['kesadaran'],
                'catatan_rujukan' => $data['catatan_rujukan'] ?? null,
                'tindakan_yang_diberikan' => $data['tindakan_yang_diberikan'] ?? null,
                'status' => 'menunggu',
                'alert_aktif' => $perluBroadcast,
                'sudah_broadcast' => false,
                ...$hasil,
            ]);
        }, attempts: 3);

        if ($perluBroadcast) {
            BroadcastEwsAlert::dispatch($assessment)->onQueue('ews-alert');
        }

        CatatEwsLog::dispatch(
            ewsAssessmentId: $assessment->id,
            userId: $petugas->id,
            aksi: 'dibuat',
            keterangan: sprintf(
                'Rujukan baru dikirim. Skor: %d, Zona: %s. Broadcast: %s.',
                $hasil['total_skor'],
                strtoupper($hasil['zona']),
                $perluBroadcast ? 'Ya' : 'Tidak',
            ),
            payload: [
                'skor_per_param' => Arr::only($hasil, [
                    'skor_respirasi',
                    'skor_saturasi',
                    'skor_oksigen',
                    'skor_suhu',
                    'skor_td',
                    'skor_nadi',
                    'skor_kesadaran',
                ]),
                'ip' => request()->ip(),
            ],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        )->onQueue('ews-log');

        Log::info('EwsRujukanService: rujukan berhasil disimpan', [
            'assessment_id' => $assessment->id,
            'zona' => $hasil['zona'],
            'perlu_broadcast' => $perluBroadcast,
        ]);

        return $assessment;
    }

    /**
     * @throws \Throwable
     */
    public function tanganiAlert(int $assessmentId, User $dokter): EwsAssessment
    {
        $assessment = DB::transaction(function () use ($assessmentId, $dokter): EwsAssessment {
            $assessment = EwsAssessment::with('penangananOleh')
                ->lockForUpdate()
                ->findOrFail($assessmentId);

            if ($assessment->status !== 'menunggu') {
                throw new RuntimeException(
                    'Alert ini sudah ditangani oleh '.($assessment->penangananOleh?->name ?? 'petugas lain').'.',
                );
            }

            $assessment->update([
                'status' => 'ditangani',
                'alert_aktif' => false,
                'ditangani_oleh' => $dokter->id,
                'waktu_ditangani' => now(),
            ]);

            return $assessment;
        }, attempts: 3);

        CatatEwsLog::dispatch(
            ewsAssessmentId: $assessment->id,
            userId: $dokter->id,
            aksi: 'tangani',
            keterangan: 'Alert ditangani oleh dokter IGD.',
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        )->onQueue('ews-log');

        return $assessment;
    }

    /**
     * @throws \Throwable
     */
    public function simpanFeedback(int $assessmentId, User $petugasRs, string $feedbackHasil, ?string $feedbackCatatan = null): EwsAssessment
    {
        $assessment = DB::transaction(function () use ($assessmentId, $petugasRs, $feedbackHasil, $feedbackCatatan): EwsAssessment {
            $assessment = EwsAssessment::with('feedbackOleh')->lockForUpdate()->findOrFail($assessmentId);

            if ($assessment->feedback_hasil !== null) {
                throw new RuntimeException(
                    'Feedback sudah pernah disimpan oleh '.($assessment->feedbackOleh?->name ?? 'petugas lain').' dan tidak dapat diubah.',
                );
            }

            $assessment->update([
                'status' => 'selesai',
                'alert_aktif' => false,
                'ditangani_oleh' => $assessment->ditangani_oleh ?? $petugasRs->id,
                'waktu_ditangani' => $assessment->waktu_ditangani ?? now(),
                'feedback_hasil' => $feedbackHasil,
                'feedback_catatan' => $feedbackCatatan,
                'feedback_oleh' => $petugasRs->id,
                'waktu_feedback' => now(),
            ]);

            return $assessment;
        }, attempts: 3);

        CatatEwsLog::dispatch(
            ewsAssessmentId: $assessment->id,
            userId: $petugasRs->id,
            aksi: 'selesai',
            keterangan: 'Feedback rumah sakit disimpan: '.$assessment->feedback_label.'.',
            payload: [
                'feedback_hasil' => $feedbackHasil,
                'feedback_catatan' => $feedbackCatatan,
            ],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        )->onQueue('ews-log');

        return $assessment;
    }
}
