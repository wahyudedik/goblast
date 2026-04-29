@extends('layouts.app')

@section('page-title', 'Template')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Template</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Kelola template pesan Anda dengan variabel dinamis.
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('templates.create') }}"
                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Buat Template
                </a>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" action="{{ route('templates.index') }}" class="flex items-center gap-4">
                    <label for="type" class="text-sm font-medium text-gray-900">Filter berdasarkan tipe:</label>
                    <select name="type" id="type"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm"
                        onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="notification" {{ $selectedType === 'notification' ? 'selected' : '' }}>Notification
                        </option>
                        <option value="promo" {{ $selectedType === 'promo' ? 'selected' : '' }}>Promo</option>
                        <option value="reminder" {{ $selectedType === 'reminder' ? 'selected' : '' }}>Reminder</option>
                    </select>
                    @if ($selectedType)
                        <a href="{{ route('templates.index') }}" class="text-sm text-green-600 hover:text-green-700">
                            Hapus filter
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Templates List -->
        @if ($templates->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada template</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat template baru.</p>
                <div class="mt-6">
                    <a href="{{ route('templates.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Buat Template
                    </a>
                </div>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($templates as $template)
                    <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-semibold text-gray-900 truncate">
                                        {{ $template->name }}
                                    </h3>
                                    <div class="mt-1">
                                        @if ($template->type === 'notification')
                                            <span
                                                class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                Notification
                                            </span>
                                        @elseif ($template->type === 'promo')
                                            <span
                                                class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">
                                                Promo
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                Reminder
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="text-sm text-gray-600 line-clamp-3">
                                    {{ $template->content }}
                                </p>
                            </div>

                            @if ($template->variables && count($template->variables) > 0)
                                <div class="mt-4">
                                    <p class="text-xs font-medium text-gray-700">Variabel:</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach ($template->variables as $variable)
                                            <code
                                                class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-800">
                                                {{ '{' . $variable . '}' }}
                                            </code>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-6 flex items-center justify-between gap-2">
                                <div class="flex gap-2">
                                    <a href="{{ route('templates.show', $template) }}"
                                        class="text-sm font-medium text-green-600 hover:text-green-700">
                                        Lihat
                                    </a>
                                    <a href="{{ route('templates.edit', $template) }}"
                                        class="text-sm font-medium text-green-600 hover:text-green-700">
                                        Edit
                                    </a>
                                </div>
                                <div class="flex gap-2">
                                    <form action="{{ route('templates.duplicate', $template) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-sm font-medium text-gray-600 hover:text-gray-900">
                                            Duplikat
                                        </button>
                                    </form>
                                    <form action="{{ route('templates.destroy', $template) }}" method="POST"
                                        class="inline"
                                        data-confirm="Apakah Anda yakin ingin menghapus template ini? Aksi ini tidak dapat dibatalkan."
                                        data-confirm-title="Hapus Template" data-confirm-button="Ya, Hapus"
                                        data-confirm-type="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-900">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
