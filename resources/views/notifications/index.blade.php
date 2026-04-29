@extends('layouts.app')

@section('page-title', 'Notifikasi')

@section('content')
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Notifikasi</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Kelola semua notifikasi sistem Anda
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if ($unreadCount > 0)
                    <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all">
                            Tandai Semua Dibaca ({{ $unreadCount }})
                        </button>
                    </form>
                @endif
                <form action="{{ route('notifications.clear-all') }}" method="POST" class="inline"
                    data-confirm="Apakah Anda yakin ingin menghapus semua notifikasi?"
                    data-confirm-title="Hapus Semua Notifikasi" data-confirm-button="Ya, Hapus Semua"
                    data-confirm-type="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 border-2 border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all">
                        Hapus Semua
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ route('notifications.index') }}"
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ !request('filter') ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Semua
                    @if ($totalCount > 0)
                        <span
                            class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">{{ $totalCount }}</span>
                    @endif
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ request('filter') === 'unread' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Belum Dibaca
                    @if ($unreadCount > 0)
                        <span
                            class="ml-2 bg-red-100 text-red-900 py-0.5 px-2.5 rounded-full text-xs">{{ $unreadCount }}</span>
                    @endif
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'read']) }}"
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ request('filter') === 'read' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Sudah Dibaca
                    @if ($readCount > 0)
                        <span
                            class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">{{ $readCount }}</span>
                    @endif
                </a>
            </nav>
        </div>
    </div>

    <!-- Notifications List -->
    @if ($notifications->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                    d="M15 17h5l-5 5v-5zM9 17H4l5 5v-5zM12 3v18" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">
                @if (request('filter') === 'unread')
                    Tidak ada notifikasi yang belum dibaca
                @elseif(request('filter') === 'read')
                    Tidak ada notifikasi yang sudah dibaca
                @else
                    Tidak ada notifikasi
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                @if (!request('filter'))
                    Notifikasi akan muncul di sini ketika ada aktivitas sistem.
                @endif
            </p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @foreach ($notifications as $notification)
                <div
                    class="flex items-start p-4 {{ !$notification->read_at ? 'bg-blue-50' : 'bg-white' }} border-b border-gray-200 last:border-b-0 hover:bg-gray-50 transition-colors">
                    <!-- Icon -->
                    <div class="shrink-0 mr-4">
                        @if ($notification->data['type'] === 'success')
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        @elseif($notification->data['type'] === 'warning')
                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        @elseif($notification->data['type'] === 'error')
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        @else
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    {{ $notification->data['title'] ?? 'Notifikasi' }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $notification->data['message'] ?? ($notification->data['body'] ?? 'Tidak ada pesan') }}
                                </p>
                                <div class="flex items-center gap-4 mt-2">
                                    <p class="text-xs text-gray-400">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                    @if ($notification->data['action_url'] ?? null)
                                        <a href="{{ $notification->data['action_url'] }}"
                                            class="text-xs text-green-600 hover:text-green-700 font-medium">
                                            {{ $notification->data['action_text'] ?? 'Lihat Detail' }}
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 ml-4">
                                @if (!$notification->read_at)
                                    <form action="{{ route('notifications.mark-read', $notification) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs text-green-600 hover:text-green-700 font-medium">
                                            Tandai Dibaca
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('notifications.destroy', $notification) }}" method="POST"
                                    class="inline" data-confirm="Apakah Anda yakin ingin menghapus notifikasi ini?"
                                    data-confirm-title="Hapus Notifikasi" data-confirm-button="Ya, Hapus"
                                    data-confirm-type="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-medium">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Unread Indicator -->
                        @if (!$notification->read_at)
                            <div class="absolute left-2 top-1/2 transform -translate-y-1/2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if ($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->appends(request()->query())->links() }}
            </div>
        @endif
    @endif
@endsection
