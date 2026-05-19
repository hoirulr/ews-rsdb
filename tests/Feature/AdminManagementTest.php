<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManajemenFaskes;
use App\Livewire\Admin\ManajemenUser;
use App\Models\Faskes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_menambah_faskes(): void
    {
        $this->buatAdminSistem();

        Livewire::test(ManajemenFaskes::class)
            ->set('nama_faskes', 'Puskesmas Air Anyir')
            ->set('kode_faskes', 'PKM-AIR-001')
            ->set('tipe', 'puskesmas')
            ->set('alamat', 'Jl. Air Anyir')
            ->set('no_telp', '0717-123456')
            ->call('simpanFaskes')
            ->assertHasNoErrors()
            ->assertSee('Faskes berhasil ditambahkan.')
            ->assertSee('Puskesmas Air Anyir');

        $this->assertDatabaseHas('faskes', [
            'nama_faskes' => 'Puskesmas Air Anyir',
            'kode_faskes' => 'PKM-AIR-001',
            'tipe' => 'puskesmas',
        ]);
    }

    public function test_admin_dapat_menambah_user_berdasarkan_faskes(): void
    {
        $this->buatAdminSistem();
        Role::firstOrCreate(['name' => 'puskesmas']);

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Kenanga',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-KNG-001',
        ]);

        Livewire::test(ManajemenUser::class)
            ->set('name', 'Operator Kenanga')
            ->set('email', 'operator.kenanga@example.test')
            ->set('faskes_id', (string) $faskes->id)
            ->set('role', 'puskesmas')
            ->set('no_hp', '081234567890')
            ->set('password', 'Rujukan@2026*/')
            ->set('password_confirmation', 'Rujukan@2026*/')
            ->call('simpanUser')
            ->assertHasNoErrors()
            ->assertSee('User berhasil ditambahkan.')
            ->assertSee('Operator Kenanga');

        $user = User::where('email', 'operator.kenanga@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('Rujukan@2026*/', $user->password));
        $this->assertSame($faskes->id, $user->faskes_id);
        $this->assertTrue($user->hasRole('puskesmas'));
    }

    public function test_admin_dapat_mengedit_faskes(): void
    {
        $this->buatAdminSistem();

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Lama',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-LMA-001',
            'alamat' => 'Alamat lama',
            'no_telp' => '0700',
            'is_active' => true,
        ]);

        Livewire::test(ManajemenFaskes::class)
            ->call('bukaEditFaskes', $faskes->id)
            ->assertDispatched('open-modal')
            ->assertSet('editNamaFaskes', 'Puskesmas Lama')
            ->set('editNamaFaskes', 'RS Perujuk Baru')
            ->set('editKodeFaskes', 'RS-BARU-001')
            ->set('editTipe', 'rs_perujuk')
            ->set('editAlamat', 'Alamat baru')
            ->set('editNoTelp', '0717-999999')
            ->set('editIsActive', false)
            ->call('simpanEditFaskes')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal')
            ->assertSee('Faskes berhasil diperbarui.');

        $faskes->refresh();

        $this->assertSame('RS Perujuk Baru', $faskes->nama_faskes);
        $this->assertSame('RS-BARU-001', $faskes->kode_faskes);
        $this->assertSame('rs_perujuk', $faskes->tipe);
        $this->assertSame('Alamat baru', $faskes->alamat);
        $this->assertSame('0717-999999', $faskes->no_telp);
        $this->assertFalse($faskes->is_active);
    }

    public function test_admin_dapat_mengganti_password_user(): void
    {
        $this->buatAdminSistem();
        Role::firstOrCreate(['name' => 'puskesmas']);

        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Pemali',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-PML-001',
        ]);

        $user = User::factory()->create([
            'faskes_id' => $faskes->id,
            'password' => Hash::make('password-lama'),
        ]);
        $user->assignRole('puskesmas');

        Livewire::test(ManajemenUser::class)
            ->call('bukaGantiPassword', $user->id)
            ->assertDispatched('open-modal')
            ->set('passwordBaru', 'Password-Baru-2026')
            ->set('passwordBaru_confirmation', 'Password-Baru-2026')
            ->call('gantiPassword')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal')
            ->assertSee('Password user berhasil diganti.');

        $this->assertTrue(Hash::check('Password-Baru-2026', $user->refresh()->password));
    }

    public function test_admin_dapat_mengedit_detail_user(): void
    {
        $this->buatAdminSistem();
        Role::firstOrCreate(['name' => 'puskesmas']);
        Role::firstOrCreate(['name' => 'rs_perujuk']);

        $faskesAwal = Faskes::create([
            'nama_faskes' => 'Puskesmas Awal',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-AWL-001',
        ]);

        $faskesBaru = Faskes::create([
            'nama_faskes' => 'RS Baru',
            'tipe' => 'rs_perujuk',
            'kode_faskes' => 'RS-BRU-001',
        ]);

        $user = User::factory()->create([
            'name' => 'Operator Lama',
            'email' => 'operator.lama@example.test',
            'faskes_id' => $faskesAwal->id,
            'no_hp' => '0800000000',
            'is_active' => true,
        ]);
        $user->assignRole('puskesmas');

        Livewire::test(ManajemenUser::class)
            ->call('bukaEditUser', $user->id)
            ->assertDispatched('open-modal')
            ->assertSet('editName', 'Operator Lama')
            ->set('editName', 'Operator Baru')
            ->set('editEmail', 'operator.baru@example.test')
            ->set('editFaskesId', (string) $faskesBaru->id)
            ->set('editRole', 'rs_perujuk')
            ->set('editNoHp', '0811111111')
            ->set('editIsActive', false)
            ->call('simpanEditUser')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal')
            ->assertSee('Detail user berhasil diperbarui.');

        $user->refresh();

        $this->assertSame('Operator Baru', $user->name);
        $this->assertSame('operator.baru@example.test', $user->email);
        $this->assertSame($faskesBaru->id, $user->faskes_id);
        $this->assertSame('0811111111', $user->no_hp);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->hasRole('rs_perujuk'));
        $this->assertFalse($user->hasRole('puskesmas'));
    }

    private function buatAdminSistem(): User
    {
        Role::firstOrCreate(['name' => 'admin_sistem']);

        $admin = User::factory()->create();
        $admin->assignRole('admin_sistem');

        $this->actingAs($admin);

        return $admin;
    }
}
