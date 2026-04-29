@extends('layouts.app')

@section('page-title', 'Token API')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Token API</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Kelola token API untuk integrasi eksternal. Jaga keamanan token Anda dan jangan pernah membagikannya
                    secara publik.
                    <a href="{{ route('api-docs.index') }}" class="text-green-600 hover:text-green-700 font-medium">
                        Lihat Dokumentasi API &rarr;
                    </a>
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('api-tokens.create') }}"
                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Buat Token
                </a>
            </div>
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

        <!-- Tokens List -->
        @if ($tokens->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada token API</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat token API baru untuk integrasi eksternal.</p>
                <div class="mt-6">
                    <a href="{{ route('api-tokens.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Buat Token
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">
                                Nama</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                Dibuat</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                Terakhir Digunakan</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                Status</th>
                            <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($tokens as $token)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                    {{ $token->name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <time datetime="{{ $token->created_at->toIso8601String() }}"
                                        title="{{ $token->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $token->created_at->format('M d, Y') }}
                                    </time>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    @if ($token->last_used_at)
                                        <time datetime="{{ $token->last_used_at->toIso8601String() }}"
                                            title="{{ $token->last_used_at->format('Y-m-d H:i:s') }}">
                                            {{ $token->last_used_at->diffForHumans() }}
                                        </time>
                                    @else
                                        <span class="text-gray-400">Belum pernah</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($token->revoked_at)
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                            Dicabut
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Aktif
                                        </span>
                                    @endif
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('api-tokens.show', $token) }}"
                                            class="text-green-600 hover:text-green-700">
                                            Lihat
                                        </a>

                                        @if (!$token->revoked_at)
                                            <form action="{{ route('api-tokens.revoke', $token) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin mencabut token ini? Aplikasi yang menggunakan token ini tidak akan dapat mengakses API lagi."
                                                data-confirm-title="Cabut Token" data-confirm-button="Ya, Cabut"
                                                data-confirm-type="warning">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                    Cabut
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('api-tokens.destroy', $token) }}" method="POST"
                                            class="inline"
                                            data-confirm="Apakah Anda yakin ingin menghapus token ini? Aksi ini tidak dapat dibatalkan."
                                            data-confirm-title="Hapus Token" data-confirm-button="Ya, Hapus"
                                            data-confirm-type="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
