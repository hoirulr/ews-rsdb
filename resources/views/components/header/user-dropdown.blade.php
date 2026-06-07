<div x-data="{ isOpen: false }" class="relative">
    <button @click="isOpen = !isOpen"
        class="flex items-center text-gray-700 dark:text-gray-400 gap-3 hover:text-gray-900 dark:hover:text-white">
        <span class="hidden xl:block">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400 text-right">{{ auth()->user()->getRoleNames()->first() }}</span>
        </span>

        <span
            class="flex items-center justify-center overflow-hidden rounded-full h-11 w-11 bg-brand-100 text-brand-600 font-bold text-lg dark:bg-brand-500/20 dark:text-brand-400">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </span>

        <svg class="hidden xl:block" :class="{ 'rotate-180': isOpen }" width="18" height="18"
            viewBox="0 0 24 24" fill="none">
            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="isOpen" @click.outside="isOpen = false" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-4 flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">

        <!-- User Info -->
        <div class="flex items-center gap-3 px-3 py-2 mb-2">
            <span
                class="flex items-center justify-center rounded-full h-10 w-10 bg-brand-100 text-brand-600 font-bold dark:bg-brand-500/20 dark:text-brand-400">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </span>
            <div>
                <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</span>
            </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-800">

        <!-- Menu Items -->
        <ul class="flex flex-col gap-1 py-2">
            <li>
                <a href="{{ route('profile') }}"
                    class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 rounded-lg group hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300">
                        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M20 21C20 16.5817 16.4183 13 12 13C7.58172 13 4 16.5817 4 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Profil Saya
                </a>
            </li>
        </ul>

        <hr class="border-gray-200 dark:border-gray-800">

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}" class="pt-2">
            @csrf
            <button type="submit"
                class="flex items-center gap-3 w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="text-gray-500">
                    <path d="M15 3H7C5.89543 3 5 3.89543 5 5V19C5 20.1046 5.89543 21 7 21H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M19 12H9M19 12L16 9M19 12L16 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Keluar
            </button>
        </form>
    </div>
</div>
