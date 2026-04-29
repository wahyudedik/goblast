@extends('layouts.app')

@section('page-title', 'Edit Tenant')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.tenants.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Kelola
                            Tenant</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                    clip-rule="evenodd" />
                            </svg>
                            <a href="{{ route('admin.tenants.show', $tenant) }}"
                                class="ms-2 text-sm text-gray-500 hover:text-gray-700">{{ $tenant->name }}</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="ms-2 text-sm font-medium text-gray-900">Edit</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Edit Tenant</h1>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <form action="{{ route('admin.tenants.update', $tenant) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Nama Tenant</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $tenant->name) }}"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-4">
                            <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $tenant->email) }}"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-3">
                            <label for="phone" class="block text-sm font-semibold text-gray-900 mb-2">Nomor Telepon</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $tenant->phone) }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                placeholder="Contoh: 6281234567890">
                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Current Status (read-only) -->
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Status Saat Ini</label>
                            <div class="mt-2">
                                @include('admin.tenants._status-badge', ['status' => $tenant->status])
                            </div>
                            <p class="mt-2 text-sm text-gray-600">Gunakan tombol Suspend/Aktifkan di halaman detail untuk
                                mengubah status.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.tenants.show', $tenant) }}"
                        class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
