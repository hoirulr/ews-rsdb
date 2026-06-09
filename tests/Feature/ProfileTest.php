<?php

namespace Tests\Feature;

use App\Models\Faskes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        Role::firstOrCreate(['name' => 'puskesmas']);
        $user = User::factory()->create();
        $user->assignRole('puskesmas');

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-faskes-detail-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_faskes_can_update_their_faskes_detail(): void
    {
        $faskes = Faskes::create([
            'nama_faskes' => 'Puskesmas Lama',
            'tipe' => 'puskesmas',
            'kode_faskes' => 'PKM-LMA-001',
            'alamat' => 'Alamat lama',
            'no_telp' => '0700',
        ]);

        $user = User::factory()->create(['faskes_id' => $faskes->id]);

        $this->actingAs($user);

        Volt::test('profile.update-faskes-detail-form')
            ->set('nama_faskes', 'Puskesmas Baru')
            ->set('kode_faskes', 'PKM-BRU-001')
            ->set('alamat', 'Alamat baru')
            ->set('no_telp', '0717-123456')
            ->call('updateFaskesDetail')
            ->assertHasNoErrors()
            ->assertDispatched('faskes-detail-updated');

        $faskes->refresh();

        $this->assertSame('Puskesmas Baru', $faskes->nama_faskes);
        $this->assertSame('PKM-BRU-001', $faskes->kode_faskes);
        $this->assertSame('Alamat baru', $faskes->alamat);
        $this->assertSame('0717-123456', $faskes->no_telp);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }
}
