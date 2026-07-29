<?php

namespace Tests\Feature;

use App\Livewire\DetailRujukanRumahSakit;
use App\Models\EwsAssessment;
use App\Models\Faskes;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DetailRujukanRumahSakitTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_rsud_dapat_menyimpan_feedback_dan_didireksi_ke_dashboard(): void
    {
        Role::create(['name' => 'admin_rsud']);

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Sungailiat',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-SGL',
        ]);

        $rsud = Faskes::create([
            'nama_faskes' => 'RSUD Depati Bahrin',
            'tipe' => 'rsud',
            'kode_faskes' => 'RSUD-DPB',
        ]);

        $puskesmasUser = User::factory()->create(['faskes_id' => $faskes->id]);
        $petugasRs = User::factory()->create(['faskes_id' => $rsud->id]);
        $petugasRs->assignRole('admin_rsud');

        $patient = Patient::create([
            'nama_pasien' => 'Slamet',
            'no_rm' => 'RM-100',
            'tanggal_lahir' => '1975-01-01',
            'jenis_kelamin' => 'L',
            'faskes_asal_id' => $faskes->id,
        ]);

        $assessment = EwsAssessment::create([
            'patient_id' => $patient->id,
            'faskes_id' => $faskes->id,
            'user_id' => $puskesmasUser->id,
            'waktu_penilaian' => now(),
            'respirasi' => 20,
            'saturasi_o2' => 97,
            'oksigen_tambahan' => false,
            'suhu' => 37.0,
            'td_sistolik' => 120,
            'nadi' => 80,
            'kesadaran' => 'A',
            'total_skor' => 0,
            'zona' => 'hijau',
            'status' => 'menunggu',
        ]);

        Livewire::actingAs($petugasRs)
            ->test(DetailRujukanRumahSakit::class, ['assessment' => $assessment])
            ->set('feedbackHasil', 'rawat_inap_lebih_24_jam')
            ->set('feedbackCatatan', 'Pasien dirawat di bangsal.')
            ->call('simpanFeedback')
            ->assertHasNoErrors()
            ->assertRedirect(route('igd.dashboard'));

        $this->assertEquals('selesai', $assessment->fresh()->status);
        $this->assertEquals('rawat_inap_lebih_24_jam', $assessment->fresh()->feedback_hasil);
        $this->assertEquals('Pasien dirawat di bangsal.', $assessment->fresh()->feedback_catatan);

        // Feedback kedua harus ditolak dan tidak menimpa feedback pertama.
        Livewire::actingAs($petugasRs)
            ->test(DetailRujukanRumahSakit::class, ['assessment' => $assessment->fresh()])
            ->set('feedbackHasil', 'meninggal')
            ->call('simpanFeedback')
            ->assertSet('pesanError', fn (string $pesan): bool => str_contains($pesan, 'sudah pernah disimpan'));

        $this->assertEquals('rawat_inap_lebih_24_jam', $assessment->fresh()->feedback_hasil);
    }
}
