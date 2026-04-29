@extends('layouts.app')

@section('page-title', 'Tambah Paket')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Kelola
                            Paket</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="ms-2 text-sm font-medium text-gray-900">Tambah Paket</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Tambah Paket Baru</h1>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <form action="{{ route('admin.plans.store') }}" method="POST">
                @csrf

                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                        <!-- Name -->
                        <div class="sm:col-span-4">
                            <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Nama Paket</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                placeholder="Contoh: Starter, Pro, Business">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Slug -->
                        <div class="sm:col-span-3">
                            <label for="slug" class="block text-sm font-semibold text-gray-900 mb-2">Slug</label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                placeholder="contoh: starter, pro, business">
                            @error('slug')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Price -->
                        <div class="sm:col-span-2">
                            <label for="price" class="block text-sm font-semibold text-gray-900 mb-2">Harga (Rp)</label>
                            <input type="number" id="price" name="price" value="{{ old('price', 0) }}" required
                                min="0" step="0.01"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @error('price')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Message Quota -->
                        <div class="sm:col-span-2">
                            <label for="message_quota" class="block text-sm font-semibold text-gray-900 mb-2">Kuota Pesan</label>
                            <input type="number" id="message_quota" name="message_quota" value="{{ old('message_quota') }}"
                                min="0"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors disabled:bg-gray-100 disabled:text-gray-500"
                                {{ old('unlimited_quota') ? 'disabled' : '' }}>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="unlimited_quota" name="unlimited_quota" value="1"
                                        {{ old('unlimited_quota') ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500"
                                        onchange="document.getElementById('message_quota').disabled = this.checked; if(this.checked) document.getElementById('message_quota').value = '';">
                                    <span class="ms-2 text-sm text-gray-600">Unlimited</span>
                                </label>
                            </div>
                            @error('message_quota')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Max Devices -->
                        <div class="sm:col-span-2">
                            <label for="max_devices" class="block text-sm font-semibold text-gray-900 mb-2">Maksimal
                                Device</label>
                            <input type="number" id="max_devices" name="max_devices" value="{{ old('max_devices', 1) }}"
                                required min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @error('max_devices')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="sm:col-span-6">
                            <label for="description" class="block text-sm font-semibold text-gray-900 mb-2">Deskripsi</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                placeholder="Deskripsi singkat tentang paket ini">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Features Checkboxes -->
                        <div class="sm:col-span-6">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Fitur</label>
                            <div class="mt-2 space-y-3">
                                <label class="inline-flex items-center me-6">
                                    <input type="hidden" name="has_reminder" value="0">
                                    <input type="checkbox" name="has_reminder" value="1"
                                        {{ old('has_reminder') ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                                    <span class="ms-2 text-sm text-gray-700">Reminder Otomatis</span>
                                </label>
                                <label class="inline-flex items-center me-6">
                                    <input type="hidden" name="has_api" value="0">
                                    <input type="checkbox" name="has_api" value="1"
                                        {{ old('has_api') ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                                    <span class="ms-2 text-sm text-gray-700">Akses API Publik</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="hidden" name="has_multi_device" value="0">
                                    <input type="checkbox" name="has_multi_device" value="1"
                                        {{ old('has_multi_device') ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                                    <span class="ms-2 text-sm text-gray-700">Multi Device</span>
                                </label>
                            </div>
                        </div>

                        <!-- Sort Order -->
                        <div class="sm:col-span-2">
                            <label for="sort_order" class="block text-sm font-semibold text-gray-900 mb-2">Urutan Tampil</label>
                            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}"
                                min="0"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @error('sort_order')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.plans.index') }}"
                        class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                        Buat Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
