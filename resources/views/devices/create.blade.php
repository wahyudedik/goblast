@extends('layouts.app')

@section('page-title', 'Tambah Device')

@section('content')
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('devices.index') }}"
                    class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                        </path>
                    </svg>
                    Kembali ke Devices
                </a>
                <h2 class="text-2xl font-bold text-gray-900">Tambah Device Baru</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Buat koneksi device baru. Anda perlu scan QR code dengan WhatsApp untuk menyelesaikan setup.
                </p>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('devices.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Device Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">
                    Nama Device <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    placeholder="Contoh: Customer Service Utama, Marketing Team, dll"
                    class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('name') border-red-500 @else border-gray-300 @enderror">
                @error('name')
                    <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-2 text-sm text-gray-600">
                    Berikan nama deskriptif untuk membantu Anda mengidentifikasi device ini nanti.
                </p>
            </div>

            <!-- Info Box -->
            <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-blue-900 mb-1">Langkah Selanjutnya</h3>
                        <p class="text-sm text-blue-800">
                            Setelah membuat device, Anda akan diarahkan ke halaman QR code. Scan QR code tersebut dengan
                            WhatsApp Anda untuk menghubungkan device.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('devices.index') }}"
                    class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                    Buat Device
                </button>
            </div>
        </form>
    </div>
@endsection
