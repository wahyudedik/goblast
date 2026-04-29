@extends('layouts.app')

@section('page-title', 'Catat Pembayaran')

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
                            <span class="ms-2 text-sm font-medium text-gray-900">Catat Pembayaran</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Catat Pembayaran Baru</h1>
            <p class="mt-2 text-sm text-gray-700">Catat pembayaran tenant untuk mengaktifkan atau memperpanjang langganan.
            </p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <form action="{{ route('admin.invoices.store') }}" method="POST">
                @csrf

                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                        <!-- Tenant -->
                        <div class="sm:col-span-3">
                            <label for="tenant_id" class="block text-sm font-semibold text-gray-900 mb-2">Tenant</label>
                            <select id="tenant_id" name="tenant_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                                <option value="">Pilih Tenant</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}"
                                        {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tenant_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Plan -->
                        <div class="sm:col-span-3">
                            <label for="plan_id" class="block text-sm font-semibold text-gray-900 mb-2">Paket</label>
                            <select id="plan_id" name="plan_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                onchange="updatePlanInfo()">
                                <option value="">Pilih Paket</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" data-price="{{ $plan->price }}"
                                        data-quota="{{ $plan->message_quota ?? 'Unlimited' }}"
                                        {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - Rp {{ number_format($plan->price, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Duration Days -->
                        <div class="sm:col-span-2">
                            <label for="duration_days" class="block text-sm font-semibold text-gray-900 mb-2">Durasi (Hari)</label>
                            <input type="number" id="duration_days" name="duration_days"
                                value="{{ old('duration_days', 30) }}" required min="1" max="365"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                onchange="calculatePeriod()">
                            <p class="mt-2 text-sm text-gray-600">Durasi langganan dalam hari (1-365).</p>
                            @error('duration_days')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Amount -->
                        <div class="sm:col-span-2">
                            <label for="amount" class="block text-sm font-semibold text-gray-900 mb-2">Nominal (Rp)</label>
                            <input type="number" id="amount" name="amount" value="{{ old('amount') }}" required
                                min="0" step="0.01"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                placeholder="0">
                            @error('amount')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Paid At -->
                        <div class="sm:col-span-2">
                            <label for="paid_at" class="block text-sm font-semibold text-gray-900 mb-2">Tanggal Bayar</label>
                            <input type="date" id="paid_at" name="paid_at"
                                value="{{ old('paid_at', now()->format('Y-m-d')) }}" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                onchange="calculatePeriod()">
                            @error('paid_at')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-6">
                            <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Catatan</label>
                            <textarea id="notes" name="notes" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Subscription Period Preview -->
                    <div id="period-preview" class="mt-6 rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                        <div class="flex">
                            <svg class="size-5 text-blue-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div class="ms-3">
                                <h4 class="text-sm font-medium text-blue-800">Periode Langganan</h4>
                                <p class="mt-1 text-sm text-blue-700" id="period-text">
                                    Pilih tanggal bayar dan durasi untuk melihat periode langganan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.invoices.index') }}"
                        class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                        Simpan & Aktifkan Langganan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updatePlanInfo() {
            const select = document.getElementById('plan_id');
            const option = select.options[select.selectedIndex];
            const amountInput = document.getElementById('amount');

            if (option.value && option.dataset.price) {
                amountInput.value = option.dataset.price;
            }
        }

        function calculatePeriod() {
            const paidAt = document.getElementById('paid_at').value;
            const durationDays = parseInt(document.getElementById('duration_days').value) || 0;
            const periodText = document.getElementById('period-text');

            if (paidAt && durationDays > 0) {
                const startDate = new Date(paidAt);
                const endDate = new Date(paidAt);
                endDate.setDate(endDate.getDate() + durationDays);

                const formatDate = (date) => {
                    return date.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                };

                periodText.textContent =
                    `Mulai: ${formatDate(startDate)} — Berakhir: ${formatDate(endDate)} (${durationDays} hari)`;
            } else {
                periodText.textContent = 'Pilih tanggal bayar dan durasi untuk melihat periode langganan.';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculatePeriod();
        });
    </script>
@endsection
