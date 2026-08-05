<x-app-layout>
    <x-slot name="title">Profil - EWS RSUD Depati Bahrin</x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Profil Saya</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola informasi profil, password, dan pengaturan akun Anda.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <livewire:profile.update-profile-information-form />
            </div>

            @if(auth()->user()->hasRole('admin_sistem') || auth()->user()->hasRole('admin_rsud') || auth()->user()->hasRole('rs_perujuk') || auth()->user()->hasRole('puskesmas'))
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <livewire:profile.update-faskes-detail-form />
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <livewire:profile.update-password-form />
            </div>
        </div>
    </div>
</x-app-layout>
