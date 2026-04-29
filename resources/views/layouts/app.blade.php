<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false }" x-init="sidebarOpen = window.innerWidth >= 1024">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Top Bar -->
            <div
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile menu button -->
                <button @click="sidebarOpen = true" type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Page Title -->
                    <div class="flex items-center flex-1">
                        @isset($header)
                            {{ $header }}
                        @else
                            <h1 class="text-lg font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                        @endisset
                    </div>

                    <!-- Right side items -->
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Notifications dropdown -->
                        <x-notification-dropdown />

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true"></div>

                        <!-- Profile dropdown -->
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    class="flex items-center gap-x-3 text-sm font-semibold leading-6 text-gray-900 hover:bg-gray-50 rounded-lg px-3 py-2 transition">
                                    <span class="sr-only">Open user menu</span>
                                    <div
                                        class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center text-white font-semibold">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    <span class="hidden lg:flex lg:items-center">
                                        <span
                                            class="text-sm font-semibold leading-6 text-gray-900">{{ Auth::user()->name }}</span>
                                        <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-sm text-gray-500 truncate">{{ Auth::user()->email }}</p>
                                </div>
                                <x-dropdown-link :href="route('profile.edit')">
                                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                            </path>
                                        </svg>
                                        {{ __('Keluar') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main class="py-6">
                <div class="px-4 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
                            <div class="flex">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                        clip-rule="evenodd" />
                                </svg>
                                <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                        clip-rule="evenodd" />
                                </svg>
                                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    @yield('content', $slot ?? '')
                </div>
            </main>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <x-confirm-modal />

    <!-- Confirm Helper Script -->
    <script>
        // Global confirm function
        window.confirmAction = function(options) {
            return new Promise((resolve) => {
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        title: options.title || 'Konfirmasi',
                        message: options.message || 'Apakah Anda yakin?',
                        confirmText: options.confirmText || 'Konfirmasi',
                        cancelText: options.cancelText || 'Batal',
                        type: options.type || 'danger',
                        onConfirm: () => resolve(true)
                    }
                }));
            });
        };

        // Auto-handle forms with data-confirm attribute
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('submit', async function(e) {
                const form = e.target;
                const confirmMessage = form.dataset.confirm;
                const confirmTitle = form.dataset.confirmTitle;
                const confirmType = form.dataset.confirmType || 'danger';
                const confirmButton = form.dataset.confirmButton || 'Konfirmasi';

                if (confirmMessage && !form.dataset.confirmed) {
                    e.preventDefault();

                    const confirmed = await confirmAction({
                        title: confirmTitle || 'Konfirmasi Aksi',
                        message: confirmMessage,
                        confirmText: confirmButton,
                        type: confirmType
                    });

                    if (confirmed) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                }
            });

            // Handle delete buttons with data-confirm
            document.addEventListener('click', async function(e) {
                const button = e.target.closest('[data-confirm]');
                if (button && button.tagName === 'BUTTON' && button.type !== 'submit') {
                    e.preventDefault();

                    const confirmed = await confirmAction({
                        title: button.dataset.confirmTitle || 'Konfirmasi Aksi',
                        message: button.dataset.confirm,
                        confirmText: button.dataset.confirmButton || 'Konfirmasi',
                        type: button.dataset.confirmType || 'danger'
                    });

                    if (confirmed && button.dataset.action) {
                        window.location.href = button.dataset.action;
                    }
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
