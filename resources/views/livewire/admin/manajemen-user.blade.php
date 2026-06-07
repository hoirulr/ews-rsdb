<div class="mx-auto max-w-6xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen User</h1>
        <p class="text-sm text-gray-500">Daftar user sistem EWS.</p>
    </div>

    @if ($pesanSukses)
        <div class="mb-4 rounded-lg border border-green-400 bg-green-100 p-4 text-green-800">{{ $pesanSukses }}</div>
    @endif

    <form wire:submit.prevent="simpanUser" class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">Tambah User</h2>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Nama *</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Email *</label>
                <input type="email" wire:model="email" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Faskes *</label>
                <select wire:model="faskes_id" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih faskes</option>
                    @foreach ($faskesList as $faskes)
                        <option value="{{ $faskes->id }}">{{ $faskes->nama_faskes }} ({{ $faskes->tipe_label }})</option>
                    @endforeach
                </select>
                @error('faskes_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Role *</label>
                <select wire:model="role" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @foreach ($roles as $roleOption)
                        <option value="{{ $roleOption->name }}">{{ $roleOption->name }}</option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">No HP</label>
                <input type="text" wire:model="no_hp" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('no_hp') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-end gap-2 pb-2 text-sm font-medium text-gray-700">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                Aktif
            </label>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Password *</label>
                <input type="password" wire:model="password" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600">Konfirmasi Password *</label>
                <input type="password" wire:model="password_confirmation" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                Tambah User
            </button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Faskes</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($users as $user)
                    <tr class="hover:bg-gray-50" wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->getRoleNames()->join(', ') }}</td>
                        <td class="px-4 py-3">{{ $user->faskes->nama_faskes ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="bukaEditUser({{ $user->id }})" class="rounded-lg bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-500 transition hover:bg-brand-100">
                                    Edit
                                </button>
                                <button type="button" wire:click="bukaGantiPassword({{ $user->id }})" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                                Ganti Password
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-modal name="edit-detail-user" maxWidth="2xl">
        <form wire:submit.prevent="simpanEditUser" class="p-6">
            <h2 class="text-lg font-semibold text-gray-800">Edit Detail User</h2>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Nama *</label>
                    <input type="text" wire:model="editName" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('editName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Email *</label>
                    <input type="email" wire:model="editEmail" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('editEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Faskes *</label>
                    <select wire:model="editFaskesId" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih faskes</option>
                        @foreach ($faskesList as $faskes)
                            <option value="{{ $faskes->id }}">{{ $faskes->nama_faskes }} ({{ $faskes->tipe_label }})</option>
                        @endforeach
                    </select>
                    @error('editFaskesId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Role *</label>
                    <select wire:model="editRole" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->name }}">{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                    @error('editRole') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">No HP</label>
                    <input type="text" wire:model="editNoHp" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('editNoHp') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-end gap-2 pb-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" wire:model="editIsActive" class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                    Aktif
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close-modal', 'edit-detail-user')" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal name="ganti-password-user" maxWidth="md">
        <form wire:submit.prevent="gantiPassword" class="p-6">
            <h2 class="text-lg font-semibold text-gray-800">Ganti Password User</h2>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Password Baru *</label>
                    <input type="password" wire:model="passwordBaru" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    @error('passwordBaru') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600">Konfirmasi Password Baru *</label>
                    <input type="password" wire:model="passwordBaru_confirmation" class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close-modal', 'ganti-password-user')" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                    Simpan Password
                </button>
            </div>
        </form>
    </x-modal>
</div>
