@extends('layouts.app')

@section('page-title', 'Edit Invoice')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Kelola
                            Invoice</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="ms-2 text-sm text-gray-500">Invoice #{{ $invoice->id }}</span>
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
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Edit Invoice</h1>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <form action="{{ route('admin.invoices.update', $invoice) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="px-4 py-5 sm:p-6">
                    <!-- Invoice Context Info -->
                    <div class="mb-6 rounded-lg bg-gray-50 p-4 border border-gray-200">
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Tenant</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $invoice->tenant->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Paket</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $invoice->plan->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Durasi</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $invoice->duration_days }} hari</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                        <!-- Amount -->
                        <div class="sm:col-span-3">
                            <label for="amount" class="block text-sm font-semibold text-gray-900 mb-2">Nominal (Rp)</label>
                            <input type="number" id="amount" name="amount"
                                value="{{ old('amount', $invoice->amount) }}" required min="0" step="0.01"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @error('amount')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Paid At -->
                        <div class="sm:col-span-3">
                            <label for="paid_at" class="block text-sm font-semibold text-gray-900 mb-2">Tanggal Bayar</label>
                            <input type="date" id="paid_at" name="paid_at"
                                value="{{ old('paid_at', $invoice->paid_at->format('Y-m-d')) }}" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            @error('paid_at')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-6">
                            <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Catatan</label>
                            <textarea id="notes" name="notes" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                placeholder="Catatan tambahan (opsional)">{{ old('notes', $invoice->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.invoices.show', $invoice) }}"
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
