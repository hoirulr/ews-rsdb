<?php

namespace Tests\Feature;

use App\Livewire\FormRujukanEws;
use App\Models\Faskes;
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
}
