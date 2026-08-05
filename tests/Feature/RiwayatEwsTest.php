<?php

namespace Tests\Feature;

use App\Livewire\DetailRiwayatEws;
use App\Livewire\RiwayatEws;
use App\Models\EwsAssessment;
use App\Models\Faskes;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RiwayatEwsTest extends TestCase
{
    use RefreshDatabase;

    public function test_riwayat_menampilkan_feedback_rumah_sakit(): void
    {
        Role::create(['name' => 'puskesmas']);

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Melati',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-MLT',
        ]);

        $rumahSakit = Faskes::create([
            'nama_faskes' => 'RSUD Sehat',
            'tipe' => 'rsud',
            'kode_faskes' => 'RSUD-SHT',
        ]);

        $user = User::factory()->create(['faskes_id' => $faskes->id]);
        $user->assignRole('puskesmas');

        $petugasRs = User::factory()->create(['faskes_id' => $rumahSakit->id]);

        $patient = Patient::create([
            'nama_pasien' => 'Budi Santoso',
            'no_rm' => 'RM-001',
            'tanggal_lahir' => '1985-05-01',
            'jenis_kelamin' => 'L',
            'faskes_asal_id' => $faskes->id,
        ]);

        EwsAssessment::create([
            'patient_id' => $patient->id,
            'faskes_id' => $faskes->id,
            'user_id' => $user->id,
            'waktu_penilaian' => now(),
            'respirasi' => 26,
            'saturasi_o2' => 90,
            'oksigen_tambahan' => true,
            'suhu' => 39.2,
            'td_sistolik' => 85,
            'nadi' => 132,
            'kesadaran' => 'V',
            'total_skor' => 18,
            'zona' => 'merah',
            'status' => 'selesai',
            'feedback_hasil' => 'icu_lebih_24_jam',
            'feedback_catatan' => 'Pasien masuk ICU dan stabil.',
            'feedback_oleh' => $petugasRs->id,
            'waktu_feedback' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('ews.riwayat'));

        $response
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Rawat Lebih dari 24 Jam di ICU')
            ->assertSee('Lihat Detail');
    }

    public function test_pencarian_memfilter_riwayat_berdasarkan_nama_dan_no_rm(): void
    {
        Role::create(['name' => 'puskesmas']);

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Melati',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-MLT',
        ]);

        $user = User::factory()->create(['faskes_id' => $faskes->id]);
        $user->assignRole('puskesmas');

        $dataVital = [
            'faskes_id' => $faskes->id,
            'user_id' => $user->id,
            'waktu_penilaian' => now(),
            'respirasi' => 18,
            'saturasi_o2' => 98,
            'oksigen_tambahan' => false,
            'suhu' => 36.5,
            'td_sistolik' => 120,
            'nadi' => 80,
            'kesadaran' => 'A',
            'total_skor' => 0,
            'zona' => 'hijau',
            'status' => 'menunggu',
        ];

        $budi = Patient::create([
            'nama_pasien' => 'Budi Santoso',
            'no_rm' => 'RM-100',
            'tanggal_lahir' => '1985-05-01',
            'jenis_kelamin' => 'L',
            'faskes_asal_id' => $faskes->id,
        ]);

        $siti = Patient::create([
            'nama_pasien' => 'Siti Aminah',
            'no_rm' => 'RM-200',
            'tanggal_lahir' => '1990-08-10',
            'jenis_kelamin' => 'P',
            'faskes_asal_id' => $faskes->id,
        ]);

        EwsAssessment::create([...$dataVital, 'patient_id' => $budi->id]);
        EwsAssessment::create([...$dataVital, 'patient_id' => $siti->id]);

        // Cari berdasarkan nama pasien
        Livewire::actingAs($user)
            ->test(RiwayatEws::class)
            ->set('search', 'Budi')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah');

        // Cari berdasarkan no RM
        Livewire::actingAs($user)
            ->test(RiwayatEws::class)
            ->set('search', 'RM-200')
            ->assertSee('Siti Aminah')
            ->assertDontSee('Budi Santoso');

        // Pencarian tanpa hasil menampilkan pesan kosong yang sesuai
        Livewire::actingAs($user)
            ->test(RiwayatEws::class)
            ->set('search', 'tidak-ada-xyz')
            ->assertSee('Tidak ada riwayat yang cocok');

        // Tombol reset mengosongkan pencarian
        Livewire::actingAs($user)
            ->test(RiwayatEws::class)
            ->set('search', 'Budi')
            ->call('resetPencarian')
            ->assertSet('search', '')
            ->assertSee('Budi Santoso')
            ->assertSee('Siti Aminah');
    }

    public function test_detail_riwayat_menampilkan_catatan_dan_feedback(): void
    {
        Role::create(['name' => 'puskesmas']);

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Melati',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-MLT',
        ]);

        $rumahSakit = Faskes::create([
            'nama_faskes' => 'RSUD Sehat',
            'tipe' => 'rsud',
            'kode_faskes' => 'RSUD-SHT',
        ]);

        $user = User::factory()->create(['faskes_id' => $faskes->id]);
        $user->assignRole('puskesmas');

        $petugasRs = User::factory()->create([
            'name' => 'Dokter IGD',
            'faskes_id' => $rumahSakit->id,
        ]);

        $patient = Patient::create([
            'nama_pasien' => 'Siti Aminah',
            'no_rm' => 'RM-002',
            'tanggal_lahir' => '1990-08-10',
            'jenis_kelamin' => 'P',
            'faskes_asal_id' => $faskes->id,
        ]);

        $assessment = EwsAssessment::create([
            'patient_id' => $patient->id,
            'faskes_id' => $faskes->id,
            'user_id' => $user->id,
            'waktu_penilaian' => now(),
            'respirasi' => 22,
            'saturasi_o2' => 94,
            'oksigen_tambahan' => false,
            'suhu' => 38.1,
            'td_sistolik' => 96,
            'nadi' => 112,
            'kesadaran' => 'A',
            'total_skor' => 5,
            'zona' => 'kuning',
            'catatan_rujukan' => 'Sesak sejak pagi.',
            'tindakan_yang_diberikan' => 'Oksigen nasal kanul.',
            'status' => 'selesai',
            'feedback_hasil' => 'rawat_inap_lebih_24_jam',
            'feedback_catatan' => 'Dirawat di ruang penyakit dalam.',
            'feedback_oleh' => $petugasRs->id,
            'waktu_feedback' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(DetailRiwayatEws::class, ['assessment' => $assessment])
            ->assertSee('Siti Aminah')
            ->assertSee('Sesak sejak pagi.')
            ->assertSee('Oksigen nasal kanul.')
            ->assertSee('Rawat lebih dari 24 Jam di ruangan rawat inap')
            ->assertSee('Dirawat di ruang penyakit dalam.')
            ->assertSee('Dokter IGD');
    }
}
