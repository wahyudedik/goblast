@extends('layouts.app')

@section('page-title', 'Edit Konfigurasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.configs.index') }}"
                            class="text-sm text-gray-500 hover:text-gray-700">Konfigurasi Sistem</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="ms-2 text-sm text-gray-500">{{ $config->key }}</span>
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
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Edit Konfigurasi</h1>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <form action="{{ route('admin.configs.update', $config) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                        <!-- Key (read-only) -->
                        <div class="sm:col-span-4">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Key</label>
                            <div class="mt-1">
                                <code
                                    class="inline-block rounded bg-gray-100 px-3 py-2 text-sm font-mono text-gray-900">{{ $config->key }}</code>
                            </div>
                        </div>

                        <!-- Type (read-only) -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Type</label>
                            <div class="mt-1">
                                <span
                                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $config->type }}</span>
                            </div>
                        </div>

                        <!-- Description (read-only) -->
                        @if ($config->description)
                            <div class="sm:col-span-6">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Description</label>
                                <p class="mt-2 text-sm text-gray-600">{{ $config->description }}</p>
                            </div>
                        @endif

                        <!-- Value -->
                        <div class="sm:col-span-4">
                            <label for="value" class="block text-sm font-semibold text-gray-900 mb-2">Value</label>

                            @if ($config->type === 'boolean')
                                <select id="value" name="value"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                                    <option value="true"
                                        {{ old('value', $config->value) === 'true' || old('value', $config->value) === '1' ? 'selected' : '' }}>
                                        True</option>
                                    <option value="false"
                                        {{ old('value', $config->value) === 'false' || old('value', $config->value) === '0' ? 'selected' : '' }}>
                                        False</option>
                                </select>
                            @elseif ($config->type === 'integer')
                                <input type="number" id="value" name="value"
                                    value="{{ old('value', $config->value) }}" required
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                                @php
                                    $ranges = [
                                        'default_rate_limit_per_hour' => '1 - 1000',
                                        'default_delay_min_seconds' => '1 - 60',
                                        'default_delay_max_seconds' => '1 - 60',
                                        'trial_duration_days' => '1 - 365',
                                        'log_retention_days' => '1 - 365',
                                        'system_log_retention_days' => '1 - 730',
                                        'max_csv_file_size_mb' => '1 - 50',
                                        'device_health_check_interval_seconds' => '10 - 3600',
                                        'gateway_timeout_seconds' => '5 - 120',
                                    ];
                                @endphp
                                @if (isset($ranges[$config->key]))
                                    <p class="mt-2 text-sm text-gray-600">Range yang valid:
                                        {{ $ranges[$config->key] }}</p>
                                @endif
                            @elseif ($config->type === 'json')
                                <textarea id="value" name="value" rows="6" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm">{{ old('value', json_encode(json_decode($config->value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: $config->value) }}</textarea>
                                <p class="mt-2 text-sm text-gray-600">Masukkan JSON yang valid.</p>
                            @else
                                <input type="text" id="value" name="value"
                                    value="{{ old('value', $config->value) }}" required
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @endif

                            @error('value')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.configs.index') }}"
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
