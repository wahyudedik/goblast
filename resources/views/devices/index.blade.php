@extends('layouts.app')

@section('page-title', 'Device')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Device</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Kelola device WhatsApp Anda. Anda menggunakan {{ $currentDeviceCount }} dari {{ $maxDevices }}
                    device{{ $maxDevices > 1 ? '' : '' }}.
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                @if ($canAddDevice)
                    <a href="{{ route('devices.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Tambah Device
                    </a>
                @else
                    <button type="button" disabled
                        class="inline-flex items-center rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-500 cursor-not-allowed">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Batas Device Tercapai
                    </button>
                @endif
            </div>
        </div>

        <!-- Devices List -->
        @if ($devices->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada device</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan device baru.</p>
                @if ($canAddDevice)
                    <div class="mt-6">
                        <a href="{{ route('devices.create') }}"
                            class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                            <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Tambah Device
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">
                                Nama</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nomor
                                Telepon</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Terakhir
                                Aktif
                            </th>
                            <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($devices as $device)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                    {{ $device->name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $device->phone_number ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($device->status === 'connected')
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Terhubung
                                        </span>
                                    @elseif ($device->status === 'pending')
                                        <span
                                            class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-yellow-500" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Menunggu
                                        </span>
                                    @elseif ($device->status === 'disconnected')
                                        <span
                                            class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-gray-400" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Terputus
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-red-500" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Error
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    @if ($device->last_seen_at)
                                        <time datetime="{{ $device->last_seen_at->toIso8601String() }}"
                                            title="{{ $device->last_seen_at->format('Y-m-d H:i:s') }}">
                                            {{ $device->last_seen_at->diffForHumans() }}
                                        </time>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                    <div class="flex justify-end gap-2">
                                        @if ($device->status === 'pending')
                                            <a href="{{ route('devices.connect', $device) }}"
                                                class="text-green-600 hover:text-green-700">
                                                Hubungkan
                                            </a>
                                        @else
                                            <a href="{{ route('devices.show', $device) }}"
                                                class="text-green-600 hover:text-green-700">
                                                Lihat
                                            </a>
                                        @endif

                                        @if (in_array($device->status, ['connected', 'pending']))
                                            <form action="{{ route('devices.disconnect', $device) }}" method="POST"
                                                class="inline" data-confirm="Apakah Anda yakin ingin memutuskan device ini?"
                                                data-confirm-title="Putuskan Device" data-confirm-button="Ya, Putuskan"
                                                data-confirm-type="warning">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                    Putuskan
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('devices.destroy', $device) }}" method="POST"
                                            class="inline"
                                            data-confirm="Apakah Anda yakin ingin menghapus device ini? Aksi ini tidak dapat dibatalkan."
                                            data-confirm-title="Hapus Device" data-confirm-button="Ya, Hapus"
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
