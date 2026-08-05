<?php

namespace Tests\Feature;

use App\Livewire\FormRujukanEws;
use App\Models\Faskes;
use App\Services\EwsRujukanService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormRujukanEwsTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_fails_and_dispatches_event(): void
    {
        Role::firstOrCreate(['name' => 'puskesmas']);

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Melati',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-MLT',
        ]);

        $user = User::factory()->create(['faskes_id' => $faskes->id]);
        $user->assignRole('puskesmas');

        $component = Livewire::actingAs($user)
            ->test(FormRujukanEws::class)
            // Leave required fields blank
            ->set('nama_pasien', '')
            ->call('kirimRujukan');

        $component->assertHasErrors(['nama_pasien', 'no_rm', 'tanggal_lahir'])
            ->assertDispatched('form-validation-error');
    }

    public function test_no_rm_yang_sama_dari_faskes_berbeda_membuat_pasien_terpisah(): void
    {
        Role::firstOrCreate(['name' => 'puskesmas']);

        $faskesA = Faskes::create(['nama_faskes' => 'Puskesmas A', 'tipe' => 'puskesmas', 'kode_faskes' => 'PKM-A']);
        $faskesB = Faskes::create(['nama_faskes' => 'Puskesmas B', 'tipe' => 'puskesmas', 'kode_faskes' => 'PKM-B']);

        $userA = User::factory()->create(['faskes_id' => $faskesA->id]);
        $userB = User::factory()->create(['faskes_id' => $faskesB->id]);
        $userA->assignRole('puskesmas');
        $userB->assignRole('puskesmas');

        $data = [
            'nama_pasien' => 'Pasien Satu',
            'no_rm' => 'RM-0001',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'waktu_penilaian' => now()->format('Y-m-d H:i'),
            'respirasi' => 18,
            'saturasi_o2' => 98,
            'oksigen_tambahan' => false,
            'suhu' => 36.5,
            'td_sistolik' => 120,
            'nadi' => 80,
            'kesadaran' => 'A',
        ];

        $service = app(EwsRujukanService::class);

        $assessmentA = $service->kirimRujukan($data, $userA);
        $assessmentB = $service->kirimRujukan([...$data, 'nama_pasien' => 'Pasien Dua'], $userB);

        $this->assertNotSame($assessmentA->patient_id, $assessmentB->patient_id);
        $this->assertSame('Pasien Satu', $assessmentA->patient->nama_pasien);
        $this->assertSame('Pasien Dua', $assessmentB->patient->nama_pasien);
    }
}
