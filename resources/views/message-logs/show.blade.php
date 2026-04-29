@extends('layouts.app')

@section('title', 'Message Log Details')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('message-logs.index') }}" class="text-gray-400 hover:text-gray-500">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Message Log Details</h1>
                </div>
                <p class="mt-2 text-sm text-gray-700">
                    View detailed information about this message delivery.
                </p>
            </div>
            <div class="mt-4 flex gap-2 sm:mt-0">
                @if ($messageLog->status === 'failed')
                    <form action="{{ route('message-logs.retry', $messageLog) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg class="-ms-0.5 me-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Retry
                        </button>
                    </form>
                @endif

                <form action="{{ route('message-logs.destroy', $messageLog) }}" method="POST" class="inline"
                    data-confirm="Apakah Anda yakin ingin menghapus log pesan ini?" data-confirm-title="Hapus Log Pesan"
                    data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        <svg class="-ms-0.5 me-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <!-- Status Badge -->
        <div>
            @if ($messageLog->status === 'sent')
                <span
                    class="inline-flex items-center rounded-md bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                    <svg class="-ms-0.5 me-1.5 size-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    Sent Successfully
                </span>
            @elseif ($messageLog->status === 'pending')
                <span
                    class="inline-flex items-center rounded-md bg-yellow-50 px-3 py-1.5 text-sm font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                    <svg class="-ms-0.5 me-1.5 size-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Pending
                </span>
            @elseif ($messageLog->status === 'retrying')
                <span
                    class="inline-flex items-center rounded-md bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                    <svg class="-ms-0.5 me-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Retrying (Attempt {{ $messageLog->attempts }})
                </span>
            @elseif ($messageLog->status === 'cancelled')
                <span
                    class="inline-flex items-center rounded-md bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                    <svg class="-ms-0.5 me-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Cancelled
                </span>
            @else
                <span
                    class="inline-flex items-center rounded-md bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                    <svg class="-ms-0.5 me-1.5 size-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    Failed
                </span>
            @endif
        </div>

        <!-- Message Details -->
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900">Message Information</h3>
                <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Recipient</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $messageLog->recipient }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Device</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($messageLog->device)
                                <a href="{{ route('devices.show', $messageLog->device) }}"
                                    class="text-green-600 hover:text-green-700">
                                    {{ $messageLog->device->name }}
                                </a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Source</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($messageLog->source) }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Attempts</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $messageLog->attempts }}</dd>
                    </div>

                    @if ($messageLog->broadcast)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Broadcast</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('broadcasts.show', $messageLog->broadcast) }}"
                                    class="text-green-600 hover:text-green-700">
                                    {{ $messageLog->broadcast->name }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    @if ($messageLog->reminder)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Reminder</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $messageLog->reminder->name }}
                            </dd>
                        </div>
                    @endif

                    @if ($messageLog->template)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Template</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('templates.show', $messageLog->template) }}"
                                    class="text-green-600 hover:text-green-700">
                                    {{ $messageLog->template->name }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $messageLog->created_at->format('Y-m-d H:i:s') }}
                            <span class="text-gray-500">
                                ({{ $messageLog->created_at->diffForHumans() }})
                            </span>
                        </dd>
                    </div>

                    @if ($messageLog->sent_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sent At</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $messageLog->sent_at->format('Y-m-d H:i:s') }}
                                <span class="text-gray-500">
                                    ({{ $messageLog->sent_at->diffForHumans() }})
                                </span>
                            </dd>
                        </div>
                    @endif

                    @if ($messageLog->failed_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Failed At</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $messageLog->failed_at->format('Y-m-d H:i:s') }}
                                <span class="text-gray-500">
                                    ({{ $messageLog->failed_at->diffForHumans() }})
                                </span>
                            </dd>
                        </div>
                    @endif

                    @if ($messageLog->job_id)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Job ID</dt>
                            <dd class="mt-1 font-mono text-xs text-gray-900">{{ $messageLog->job_id }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Message Content -->
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900">Message Content</h3>
                <div class="mt-4 rounded-lg bg-gray-50 p-4">
                    <p class="whitespace-pre-wrap text-sm text-gray-900">{{ $messageLog->message }}</p>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        @if ($messageLog->error_message)
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-red-900">Error Message</h3>
                    <div class="mt-4 rounded-lg bg-red-50 p-4">
                        <p class="whitespace-pre-wrap text-sm text-red-900">
                            {{ $messageLog->error_message }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Retry History -->
        @if ($retryHistory->isNotEmpty())
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Retry History</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Previous attempts to send this message to the same recipient.
                    </p>
                    <div class="mt-4 flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach ($retryHistory as $index => $retry)
                                <li>
                                    <div class="relative pb-8">
                                        @if (!$loop->last)
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"
                                                aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span
                                                    class="flex size-8 items-center justify-center rounded-full {{ $retry->status === 'sent' ? 'bg-green-500' : 'bg-red-500' }} ring-8 ring-white">
                                                    @if ($retry->status === 'sent')
                                                        <svg class="size-5 text-white" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    @else
                                                        <svg class="size-5 text-white" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        Attempt {{ $retry->attempts }} -
                                                        <span
                                                            class="font-medium {{ $retry->status === 'sent' ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ ucfirst($retry->status) }}
                                                        </span>
                                                    </p>
                                                    @if ($retry->error_message)
                                                        <p class="mt-1 text-xs text-red-600">
                                                            {{ $retry->error_message }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                    <time datetime="{{ $retry->created_at->toIso8601String() }}">
                                                        {{ $retry->created_at->format('M d, H:i') }}
                                                    </time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
