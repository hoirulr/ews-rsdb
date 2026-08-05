<?php

namespace App\Livewire\Admin;

use App\Models\Faskes;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class ManajemenUser extends Component
{
    use WithPagination;

    public string $name = '';

    public string $email = '';

    public string $faskes_id = '';

    public string $role = 'puskesmas';

    public string $no_hp = '';

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $userEditId = null;

    public string $editName = '';

    public string $editEmail = '';

    public string $editFaskesId = '';

    public string $editRole = 'puskesmas';

    public string $editNoHp = '';

    public bool $editIsActive = true;

    public ?int $userPasswordId = null;

    public string $passwordBaru = '';

    public string $passwordBaru_confirmation = '';

    public string $pesanSukses = '';

    public function simpanUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'faskes_id' => ['required', 'integer', Rule::exists('faskes', 'id')],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'no_hp' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'faskes_id' => (int) $validated['faskes_id'],
            'no_hp' => $validated['no_hp'],
            'is_active' => $validated['is_active'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        $this->reset(['name', 'email', 'faskes_id', 'no_hp', 'password', 'password_confirmation']);
        $this->role = 'puskesmas';
        $this->is_active = true;
        $this->pesanSukses = 'User berhasil ditambahkan.';
    }

    public function bukaEditUser(int $userId): void
    {
        $user = User::with('roles')->findOrFail($userId);

        $this->userEditId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editFaskesId = (string) $user->faskes_id;
        $this->editRole = $user->roles->first()?->name ?? 'puskesmas';
        $this->editNoHp = $user->no_hp ?? '';
        $this->editIsActive = (bool) $user->is_active;
        $this->resetValidation([
            'editName',
            'editEmail',
            'editFaskesId',
            'editRole',
            'editNoHp',
            'editIsActive',
        ]);

        $this->dispatch('open-modal', 'edit-detail-user');
    }

    public function simpanEditUser(): void
    {
        $user = User::query()->findOrFail($this->userEditId);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'editFaskesId' => ['required', 'integer', Rule::exists('faskes', 'id')],
            'editRole' => ['required', 'string', Rule::exists('roles', 'name')],
            'editNoHp' => ['nullable', 'string', 'max:255'],
            'editIsActive' => ['boolean'],
        ]);

        $user->update([
            'name' => $validated['editName'],
            'email' => $validated['editEmail'],
            'faskes_id' => (int) $validated['editFaskesId'],
            'no_hp' => $validated['editNoHp'],
            'is_active' => $validated['editIsActive'],
        ]);
        $user->syncRoles([$validated['editRole']]);

        $this->reset([
            'userEditId',
            'editName',
            'editEmail',
            'editFaskesId',
            'editNoHp',
        ]);
        $this->editRole = 'puskesmas';
        $this->editIsActive = true;
        $this->pesanSukses = 'Detail user berhasil diperbarui.';

        $this->dispatch('close-modal', 'edit-detail-user');
    }

    public function bukaGantiPassword(int $userId): void
    {
        User::query()->findOrFail($userId);

        $this->userPasswordId = $userId;
        $this->reset(['passwordBaru', 'passwordBaru_confirmation']);
        $this->resetValidation(['passwordBaru']);

        $this->dispatch('open-modal', 'ganti-password-user');
    }

    public function gantiPassword(): void
    {
        $validated = $this->validate([
            'passwordBaru' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->findOrFail($this->userPasswordId);
        $user->forceFill([
            'password' => Hash::make($validated['passwordBaru']),
        ])->save();

        $this->reset(['userPasswordId', 'passwordBaru', 'passwordBaru_confirmation']);
        $this->pesanSukses = 'Password user berhasil diganti.';

        $this->dispatch('close-modal', 'ganti-password-user');
    }

    public function render()
    {
        return view('livewire.admin.manajemen-user', [
            'users' => User::with('faskes')->orderBy('name')->paginate(25),
            'faskesList' => Faskes::orderBy('nama_faskes')->get(),
            'roles' => Role::orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Manajemen User']);
    }
}
