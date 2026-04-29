@extends('layouts.app')

@section('page-title', 'Broadcast')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Broadcast</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Kirim pesan ke banyak penerima sekaligus.
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('broadcasts.create') }}"
                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Buat Broadcast
                </a>
            </div>
        </div>

        <!-- Status Filter -->
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('broadcasts.index') }}"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium {{ !request('status') ? 'bg-green-100 text-green-700' : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' }}">
                Semua
                @if (!request('status'))
                    <span class="ms-2 rounded-full bg-green-600 px-2 py-0.5 text-xs text-white">
                        {{ $broadcasts->total() }}
                    </span>
                @endif
            </a>
            @foreach (['draft', 'queued', 'running', 'completed', 'cancelled', 'failed'] as $status)
                <a href="{{ route('broadcasts.index', ['status' => $status]) }}"
                    class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request('status') === $status ? 'bg-green-100 text-green-700' : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' }}">
                    {{ ucfirst($status) }}
                    @if (isset($statusCounts[$status]))
                        <span
                            class="ms-2 rounded-full {{ request('status') === $status ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' }} px-2 py-0.5 text-xs">
                            {{ $statusCounts[$status] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Broadcasts List -->
        @if ($broadcasts->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada broadcast</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat broadcast baru.</p>
                <div class="mt-6">
                    <a href="{{ route('broadcasts.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Buat Broadcast
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
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Device
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Progres
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Dibuat
                            </th>
                            <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($broadcasts as $broadcast)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                    <a href="{{ route('broadcasts.show', $broadcast) }}" class="hover:text-green-600">
                                        {{ $broadcast->name }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $broadcast->device->name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($broadcast->status === 'completed')
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Completed
                                        </span>
                                    @elseif ($broadcast->status === 'running')
                                        <span
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-blue-500 animate-pulse"
                                                viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Running
                                        </span>
                                    @elseif ($broadcast->status === 'queued')
                                        <span
                                            class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                            Queued
                                        </span>
                                    @elseif ($broadcast->status === 'draft')
                                        <span
                                            class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            Draft
                                        </span>
                                    @elseif ($broadcast->status === 'cancelled')
                                        <span
                                            class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                            Cancelled
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                            Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <div class="text-xs">
                                                {{ $broadcast->sent_count }} / {{ $broadcast->total_recipients }}
                                            </div>
                                            <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-gray-200">
                                                <div class="h-full bg-green-600"
                                                    style="width: {{ $broadcast->total_recipients > 0 ? ($broadcast->sent_count / $broadcast->total_recipients) * 100 : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                        @if ($broadcast->failed_count > 0)
                                            <span class="text-xs text-red-600">
                                                {{ $broadcast->failed_count }} gagal
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <time datetime="{{ $broadcast->created_at->toIso8601String() }}"
                                        title="{{ $broadcast->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $broadcast->created_at->diffForHumans() }}
                                    </time>
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('broadcasts.show', $broadcast) }}"
                                            class="text-green-600 hover:text-green-700">
                                            Lihat
                                        </a>

                                        @if (in_array($broadcast->status, ['queued', 'running']))
                                            <form action="{{ route('broadcasts.cancel', $broadcast) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin membatalkan broadcast ini?"
                                                data-confirm-title="Batalkan Broadcast" data-confirm-button="Ya, Batalkan"
                                                data-confirm-type="warning">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                    Batalkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($broadcasts->hasPages())
                <div class="mt-6">
                    {{ $broadcasts->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
