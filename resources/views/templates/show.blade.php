@extends('layouts.app')

@section('title', $template->name)

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <a href="{{ route('templates.index') }}"
                class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="-ms-1 me-1 size-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"
                        clip-rule="evenodd" />
                </svg>
                Back to Templates
            </a>
            <div class="mt-2 sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
                    <div class="mt-2">
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
                <div class="mt-4 flex gap-3 sm:mt-0">
                    <a href="{{ route('templates.edit', $template) }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                        </svg>
                        Edit
                    </a>
                    <form action="{{ route('templates.duplicate', $template) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" />
                                <path
                                    d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" />
                            </svg>
                            Duplicate
                        </button>
                    </form>
                    <form action="{{ route('templates.destroy', $template) }}" method="POST" class="inline"
                        data-confirm="Apakah Anda yakin ingin menghapus template ini? Tindakan ini tidak dapat dibatalkan."
                        data-confirm-title="Hapus Template" data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                    clip-rule="evenodd" />
                            </svg>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Template Content -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-base font-semibold text-gray-900">Template Content</h2>
                <div class="mt-4 rounded-md border border-gray-300 bg-gray-50 p-4">
                    <p class="whitespace-pre-wrap font-mono text-sm text-gray-700">
                        {{ $template->content }}</p>
                </div>

                @if ($template->variables && count($template->variables) > 0)
                    <div class="mt-4">
                        <h3 class="text-sm font-medium text-gray-900">Variables</h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($template->variables as $variable)
                                <code
                                    class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-sm font-mono text-gray-800">
                                    {{ '{' . $variable . '}' }}
                                </code>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="grid gap-6 sm:grid-cols-2">
            <!-- Broadcasts -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Broadcasts</h2>
                        <span
                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                            {{ $template->broadcasts_count }}
                        </span>
                    </div>

                    @if ($template->broadcasts->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">
                            This template has not been used in any broadcasts yet.
                        </p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($template->broadcasts as $broadcast)
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900">
                                            {{ $broadcast->name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $broadcast->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <span
                                        class="ms-2 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                        @if ($broadcast->status === 'completed') bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20
                                        @elseif($broadcast->status === 'running') bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10
                                        @elseif($broadcast->status === 'failed') bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10
                                        @else bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10 @endif">
                                        {{ ucfirst($broadcast->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        @if ($template->broadcasts_count > 10)
                            <p class="mt-4 text-sm text-gray-500">
                                Showing 10 of {{ $template->broadcasts_count }} broadcasts.
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Reminders -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Reminders</h2>
                        <span
                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                            {{ $template->reminders_count }}
                        </span>
                    </div>

                    @if ($template->reminders->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">
                            This template has not been used in any reminders yet.
                        </p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($template->reminders as $reminder)
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900">
                                            {{ $reminder->name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ ucfirst(str_replace('_', ' ', $reminder->type)) }}
                                        </p>
                                    </div>
                                    @if ($reminder->is_active)
                                        <span
                                            class="ms-2 inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="ms-2 inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($template->reminders_count > 10)
                            <p class="mt-4 text-sm text-gray-500">
                                Showing 10 of {{ $template->reminders_count }} reminders.
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Metadata -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-base font-semibold text-gray-900">Metadata</h2>
                <dl class="mt-4 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $template->created_at->format('M d, Y \a\t H:i') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $template->updated_at->format('M d, Y \a\t H:i') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Character Count</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ strlen($template->content) }} / 4096
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Variables Count</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $template->variables ? count($template->variables) : 0 }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
