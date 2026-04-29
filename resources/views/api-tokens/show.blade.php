@extends('layouts.app')

@section('title', 'API Token Details')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <a href="{{ route('api-tokens.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="me-1 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to API Tokens
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $apiToken->name }}</h1>
            <p class="mt-2 text-sm text-gray-700">
                View details and usage statistics for this API token.
            </p>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="size-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ms-3">
                        <p class="text-sm font-medium text-green-800">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Token Display (Only shown once after creation) -->
        @if ($plainToken)
            <div class="rounded-md bg-yellow-50 p-4 ring-1 ring-yellow-600/20">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="size-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ms-3 flex-1">
                        <h3 class="text-sm font-medium text-yellow-800">Your API Token</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p class="mb-3">
                                This is the only time you will see this token. Make sure to copy it now and store it
                                securely.
                            </p>
                            <div class="flex items-center gap-2">
                                <input type="text" readonly value="{{ $plainToken }}" id="token-input"
                                    class="block flex-1 rounded-md border-0 bg-yellow-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-yellow-300 font-mono text-sm">
                                <button type="button" onclick="copyToken()"
                                    class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span class="ms-1.5" id="copy-text">Copy</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function copyToken() {
                    const input = document.getElementById('token-input');
                    const copyText = document.getElementById('copy-text');

                    input.select();
                    input.setSelectionRange(0, 99999); // For mobile devices

                    navigator.clipboard.writeText(input.value).then(() => {
                        copyText.textContent = 'Copied!';
                        setTimeout(() => {
                            copyText.textContent = 'Copy';
                        }, 2000);
                    });
                }
            </script>
        @endif

        <!-- Token Details -->
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900">Token Information</h3>
                <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5">
                        <dt class="truncate text-sm font-medium text-gray-500">Token Name</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $apiToken->name }}</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5">
                        <dt class="truncate text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if ($apiToken->revoked_at)
                                <span
                                    class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-sm font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                    Revoked
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                    <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
                                        <circle cx="3" cy="3" r="3" />
                                    </svg>
                                    Active
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5">
                        <dt class="truncate text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            <time datetime="{{ $apiToken->created_at->toIso8601String() }}"
                                title="{{ $apiToken->created_at->format('Y-m-d H:i:s') }}">
                                {{ $apiToken->created_at->format('M d, Y') }}
                            </time>
                        </dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-gray-50 px-4 py-5">
                        <dt class="truncate text-sm font-medium text-gray-500">Last Used</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            @if ($apiToken->last_used_at)
                                <time datetime="{{ $apiToken->last_used_at->toIso8601String() }}"
                                    title="{{ $apiToken->last_used_at->format('Y-m-d H:i:s') }}">
                                    {{ $apiToken->last_used_at->diffForHumans() }}
                                </time>
                            @else
                                <span class="text-gray-400">Never</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900">Usage Statistics</h3>
                <dl class="mt-5 grid grid-cols-1 gap-5">
                    <div class="overflow-hidden rounded-lg bg-green-50 px-4 py-5">
                        <dt class="truncate text-sm font-medium text-green-600">Total API Requests</dt>
                        <dd class="mt-1 text-3xl font-semibold text-green-900">{{ number_format($totalRequests) }}</dd>
                        <dd class="mt-1 text-sm text-green-600">
                            Messages sent via API for this tenant
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between gap-x-3">
            <div class="flex gap-x-3">
                @if (!$apiToken->revoked_at)
                    <form action="{{ route('api-tokens.revoke', $apiToken) }}" method="POST"
                        data-confirm="Apakah Anda yakin ingin mencabut token ini? Aplikasi yang menggunakan token ini tidak akan dapat mengakses API lagi."
                        data-confirm-title="Cabut Token" data-confirm-button="Ya, Cabut" data-confirm-type="warning">
                        @csrf
                        <button type="submit"
                            class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600">
                            Revoke Token
                        </button>
                    </form>
                @endif

                <form action="{{ route('api-tokens.destroy', $apiToken) }}" method="POST"
                    data-confirm="Apakah Anda yakin ingin menghapus token ini? Aksi ini tidak dapat dibatalkan."
                    data-confirm-title="Hapus Token" data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        Delete Token
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
