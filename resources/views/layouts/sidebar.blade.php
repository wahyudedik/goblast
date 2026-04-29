<!-- Mobile sidebar overlay -->
<div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="relative z-50 lg:hidden" role="dialog" aria-modal="true"
    style="display: none;">
    <div class="fixed inset-0 bg-gray-900/80" @click="sidebarOpen = false"></div>

    <div class="fixed inset-0 flex">
        <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full" class="relative mr-16 flex w-full max-w-xs flex-1">
            <!-- Close button -->
            <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                <button @click="sidebarOpen = false" type="button" class="-m-2.5 p-2.5">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Sidebar component for mobile -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center border-b border-gray-200">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
                        <span class="text-xl font-bold text-green-600">Konektivitas</span>
                    </a>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                @include('layouts.sidebar-links')
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Static sidebar for desktop -->
<div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-64 lg:flex-col">
    <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
        <div class="flex h-16 shrink-0 items-center border-b border-gray-200">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
                <span class="text-xl font-bold text-green-600">Konektivitas</span>
            </a>
        </div>
        <nav class="flex flex-1 flex-col">
            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                <li>
                    <ul role="list" class="-mx-2 space-y-1">
                        @include('layouts.sidebar-links')
                    </ul>
                </li>

                <!-- Bottom section -->
                @if (Auth::user()->role !== 'superadmin')
                    <li class="mt-auto">
                        <div class="rounded-lg bg-green-50 p-4 border border-green-200">
                            <div class="flex items-start gap-x-3">
                                <svg class="h-6 w-6 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-green-900">Upgrade Plan</h3>
                                    <p class="mt-1 text-xs text-green-700">Dapatkan fitur lebih lengkap</p>
                                    <a href="{{ route('subscription.plans') }}"
                                        class="mt-2 inline-block text-xs font-semibold text-green-600 hover:text-green-700">
                                        Lihat Paket →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</div>
