@extends('layouts.app')

@section('page-title', 'Auto Reply')

@section('content')
    <div class="space-y-6">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Auto Reply</h1>
                <p class="mt-2 text-sm text-gray-700">Balas pesan masuk secara otomatis berdasarkan kata kunci.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('auto-reply.create') }}"
                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Tambah Keyword
                </a>
            </div>
        </div>

        <!-- Info -->
        <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                        clip-rule="evenodd" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 mb-1">Cara Kerja Auto Reply</h3>
                    <p class="text-sm text-blue-800">Ketika seseorang mengirim pesan ke WhatsApp Anda yang mengandung kata
                        kunci tertentu, sistem akan otomatis membalas dengan pesan yang sudah Anda tentukan. Prioritas lebih
                        rendah = diproses lebih dulu.</p>
                </div>
            </div>
        </div>

        @if ($rules->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada auto reply</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan keyword dan balasan otomatis.</p>
                <div class="mt-6">
                    <a href="{{ route('auto-reply.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">Tambah
                        Keyword</a>
                </div>
            </div>
        @else
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Keyword</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Balasan
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Device</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prioritas
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6"><span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($rules as $rule)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 sm:ps-6">
                                    <code
                                        class="px-2 py-1 bg-green-50 text-green-700 rounded text-sm font-mono font-semibold">{{ $rule->keyword }}</code>
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    {{ Str::limit($rule->reply, 60) }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $rule->device->name }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $rule->priority }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($rule->is_active)
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Nonaktif</span>
                                    @endif
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('auto-reply.edit', $rule) }}"
                                            class="text-green-600 hover:text-green-700">Edit</a>
                                        <form action="{{ route('auto-reply.toggle', $rule) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="{{ $rule->is_active ? 'text-yellow-600' : 'text-green-600' }}">
                                                {{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('auto-reply.destroy', $rule) }}" method="POST"
                                            class="inline" data-confirm="Hapus auto reply ini?"
                                            data-confirm-title="Hapus Auto Reply" data-confirm-button="Ya, Hapus"
                                            data-confirm-type="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
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
