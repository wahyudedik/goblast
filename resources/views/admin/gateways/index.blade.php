@extends('layouts.app')

@section('page-title', 'Kelola Gateway')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Gateway</h1>
                <p class="mt-2 text-sm text-gray-700">Daftar semua instance Baileys Gateway yang terdaftar.</p>
            </div>
        </div>

        <!-- Gateways Table -->
        @if ($gateways->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada gateway</h3>
                <p class="mt-1 text-sm text-gray-500">Belum ada gateway instance yang terdaftar.</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Nama
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Base
                                    URL</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Terakhir Dicek</th>
                                <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($gateways as $gateway)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                        <a href="{{ route('admin.gateways.show', $gateway) }}" class="hover:text-green-600">
                                            {{ $gateway->name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $gateway->base_url }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @include('admin.gateways._status-badge', [
                                            'status' => $gateway->status,
                                        ])
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $gateway->last_checked_at?->format('d M Y H:i') ?? '-' }}
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.gateways.show', $gateway) }}"
                                                class="text-green-600 hover:text-green-700">Lihat</a>

                                            <form action="{{ route('admin.gateways.restart', $gateway) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin me-restart gateway ini?"
                                                data-confirm-title="Restart Gateway" data-confirm-button="Ya, Restart"
                                                data-confirm-type="warning">
                                                @csrf
                                                <button type="submit"
                                                    class="text-yellow-600 hover:text-yellow-700">Restart</button>
                                            </form>

                                            <form action="{{ route('admin.gateways.destroy', $gateway) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin menghapus gateway ini? Aksi ini tidak dapat dibatalkan."
                                                data-confirm-title="Hapus Gateway" data-confirm-button="Ya, Hapus"
                                                data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-600 hover:text-red-700">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($gateways->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                        {{ $gateways->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
