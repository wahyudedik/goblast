@extends('layouts.app')

@section('page-title', 'Detail Invoice')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.invoices.index') }}"
                                class="text-sm text-gray-500 hover:text-gray-700">Kelola Invoice</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="ms-2 text-sm font-medium text-gray-900">Invoice #{{ $invoice->id }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Invoice #{{ $invoice->id }}</h1>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                <a href="{{ route('admin.invoices.edit', $invoice) }}"
                    class="inline-flex items-center px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                    <svg class="-ms-0.5 me-1.5 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Edit
                </a>

                <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST"
                    data-confirm="Apakah Anda yakin ingin menghapus invoice ini? Aksi ini tidak dapat dibatalkan."
                    data-confirm-title="Hapus Invoice" data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-600/10 hover:bg-red-100">
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 lg:col-span-2">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Detail Invoice</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('admin.tenants.show', $invoice->tenant) }}"
                                    class="text-green-600 hover:text-green-700">
                                    {{ $invoice->tenant->name }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Paket</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->plan->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nominal</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">
                                Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Durasi</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->duration_days }} hari</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Bayar</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->paid_at->format('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dicatat Oleh</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->recordedBy?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Dibuat</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Terakhir Diperbarui</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->updated_at->format('d M Y H:i') }}</dd>
                        </div>
                        @if ($invoice->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $invoice->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Subscription Info -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold text-gray-900">Langganan Terkait</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        @if ($invoice->subscription)
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Paket</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $invoice->subscription->plan->name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        @if ($invoice->subscription->status === 'active')
                                            <span
                                                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                        @elseif ($invoice->subscription->status === 'expired')
                                            <span
                                                class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Expired</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">{{ ucfirst($invoice->subscription->status) }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Periode</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $invoice->subscription->starts_at->format('d M Y') }} -
                                        {{ $invoice->subscription->ends_at->format('d M Y') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kuota Terpakai</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ number_format($invoice->subscription->message_quota_used) }} /
                                        {{ $invoice->subscription->message_quota_limit ? number_format($invoice->subscription->message_quota_limit) : 'Unlimited' }}
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-gray-500">Tidak ada langganan terkait.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
