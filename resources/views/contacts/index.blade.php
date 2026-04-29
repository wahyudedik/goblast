@extends('layouts.app')

@section('page-title', 'Kontak')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kontak</h1>
                <p class="mt-2 text-sm text-gray-700">Kelola daftar kontak penerima pesan WhatsApp Anda.</p>
            </div>
            <div class="mt-4 flex gap-2 sm:mt-0">
                <button type="button" onclick="document.getElementById('import-modal').classList.remove('hidden')"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="-ms-0.5 me-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import CSV
                </button>
                <a href="{{ route('contacts.create') }}"
                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Tambah Kontak
                </a>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-4 sm:p-5">
                <form method="GET" action="{{ route('contacts.index') }}"
                    class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Cari nama atau nomor..."
                        class="flex-1 w-full sm:w-auto px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                    <select name="group"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                        <option value="">Semua Grup</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group }}"
                                {{ ($filters['group'] ?? '') === $group ? 'selected' : '' }}>{{ $group }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors">Cari</button>
                    @if (!empty($filters['search']) || !empty($filters['group']))
                        <a href="{{ route('contacts.index') }}"
                            class="text-sm text-green-600 hover:text-green-700">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Contacts List -->
        @if ($contacts->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada kontak</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan kontak atau import dari CSV.</p>
                <div class="mt-6 flex justify-center gap-3">
                    <a href="{{ route('contacts.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">Tambah
                        Kontak</a>
                </div>
            </div>
        @else
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Nama</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nomor
                                Telepon</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Grup</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ditambahkan
                            </th>
                            <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6"><span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($contacts as $contact)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm sm:ps-6">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-semibold text-xs">
                                            {{ strtoupper(substr($contact->display_name, 0, 2)) }}
                                        </div>
                                        <span class="font-medium text-gray-900">{{ $contact->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">
                                    {{ $contact->phone_number }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($contact->group)
                                        <span
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $contact->group }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $contact->created_at->diffForHumans() }}</td>
                                <td
                                    class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('contacts.edit', $contact) }}"
                                            class="text-green-600 hover:text-green-700">Edit</a>
                                        <form action="{{ route('contacts.destroy', $contact) }}" method="POST"
                                            class="inline" data-confirm="Hapus kontak ini?"
                                            data-confirm-title="Hapus Kontak" data-confirm-button="Ya, Hapus"
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

            @if ($contacts->hasPages())
                <div class="mt-6">{{ $contacts->appends($filters)->links() }}</div>
            @endif
        @endif
    </div>

    <!-- Import Modal -->
    <div id="import-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
            onclick="document.getElementById('import-modal').classList.add('hidden')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
                <form action="{{ route('contacts.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Import Kontak dari CSV</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File CSV</label>
                            <input type="file" name="csv_file" accept=".csv,.txt" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            <p class="mt-2 text-xs text-gray-500">Format: nomor_telepon,nama (satu per baris). Contoh:
                                6281234567890,John Doe</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Grup (Opsional)</label>
                            <input type="text" name="group" placeholder="Contoh: Pelanggan, VIP, Karyawan"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 text-sm">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                            class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50">Batal</button>
                        <button type="submit"
                            class="px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
