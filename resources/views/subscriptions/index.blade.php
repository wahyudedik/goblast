@extends('layouts.app')

@section('page-title', 'Langganan')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Langganan</h1>
            <p class="mt-2 text-sm text-gray-700">
                Kelola paket langganan dan lihat penggunaan Anda.
            </p>
        </div>

        <!-- Trial Status Banner -->
        @if ($isInTrial)
            <div class="rounded-lg bg-yellow-50 p-4 ring-1 ring-yellow-600/20">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="size-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ms-3">
                        <h3 class="text-sm font-medium text-yellow-800">Masa Percobaan</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>
                                Anda memiliki <strong>{{ $trialDaysRemaining }}
                                    hari</strong> tersisa dalam masa percobaan.
                                Berlangganan paket untuk terus menggunakan layanan setelah masa percobaan berakhir.
                            </p>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('subscription.plans') }}"
                                class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                                Lihat Paket
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Current Subscription Status -->
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-base font-semibold text-gray-900">Langganan Saat Ini</h2>

                @if ($currentSubscription)
                    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Plan Name -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Paket</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ $currentSubscription->plan->name }}
                            </dd>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Mulai</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $currentSubscription->starts_at->format('M d, Y') }}
                            </dd>
                        </div>

                        <!-- End Date -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Berakhir</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $currentSubscription->ends_at->format('M d, Y') }}
                            </dd>
                        </div>

                        <!-- Days Remaining -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sisa Hari</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ (int) max(0, now()->diffInDays($currentSubscription->ends_at, false)) }} hari
                            </dd>
                        </div>
                    </div>

                    <!-- Quota Usage -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm font-medium text-gray-500">Kuota Pesan</dt>
                            <dd class="text-sm font-semibold text-gray-900">
                                @if ($remainingQuota === 'Unlimited')
                                    Tidak Terbatas
                                @else
                                    {{ number_format($remainingQuota) }} /
                                    {{ number_format($currentSubscription->message_quota_limit) }}
                                @endif
                            </dd>
                        </div>
                        @if ($remainingQuota !== 'Unlimited')
                            <div class="mt-2">
                                <div class="overflow-hidden rounded-full bg-gray-200">
                                    <div class="h-2 rounded-full {{ $quotaPercentage >= 90 ? 'bg-red-600' : ($quotaPercentage >= 70 ? 'bg-yellow-600' : 'bg-green-600') }}"
                                        style="width: {{ min(100, $quotaPercentage) }}%"></div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ number_format($quotaPercentage, 1) }}% terpakai
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Subscription Status -->
                    @if ($currentSubscription->ends_at->isPast())
                        <div class="mt-6 rounded-lg bg-red-50 p-4 ring-1 ring-red-600/20">
                            <p class="text-sm text-red-800">
                                Langganan Anda telah berakhir. Silakan perpanjang untuk terus menggunakan layanan.
                            </p>
                        </div>
                    @elseif (now()->diffInDays($currentSubscription->ends_at, false) <= 7)
                        <div class="mt-6 rounded-lg bg-yellow-50 p-4 ring-1 ring-yellow-600/20">
                            <p class="text-sm text-yellow-800">
                                Langganan Anda akan segera berakhir. Hubungi kami untuk memperpanjang langganan Anda.
                            </p>
                        </div>
                    @endif
                @else
                    <div class="mt-6 text-center">
                        <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada langganan aktif</h3>
                        <p class="mt-1 text-sm text-gray-500">Mulai dengan berlangganan paket.</p>
                        <div class="mt-6">
                            <a href="{{ route('subscription.plans') }}"
                                class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                                Lihat Paket
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Plan Features Comparison -->
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-base font-semibold text-gray-900">Paket Tersedia</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Bandingkan fitur dan pilih paket yang paling sesuai dengan kebutuhan Anda.
                </p>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-0">
                                    Fitur
                                </th>
                                @foreach ($plans as $plan)
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        {{ $plan->name }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- Price -->
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-0">
                                    Harga
                                </td>
                                @foreach ($plans as $plan)
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        Rp {{ number_format($plan->price, 0, ',', '.') }}/bulan
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Message Quota -->
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-0">
                                    Kuota Pesan
                                </td>
                                @foreach ($plans as $plan)
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($plan->message_quota === null)
                                            Tidak Terbatas
                                        @else
                                            {{ number_format($plan->message_quota) }} pesan
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Max Devices -->
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-0">
                                    Device
                                </td>
                                @foreach ($plans as $plan)
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($plan->has_multi_device)
                                            Multi device
                                        @else
                                            {{ $plan->max_devices }} device{{ $plan->max_devices > 1 ? '' : '' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Reminder Feature -->
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-0">
                                    Reminder
                                </td>
                                @foreach ($plans as $plan)
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($plan->has_reminder)
                                            <svg class="size-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg class="size-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- API Access -->
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-0">
                                    Akses API
                                </td>
                                @foreach ($plans as $plan)
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($plan->has_api)
                                            <svg class="size-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg class="size-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Action Buttons -->
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-0">
                                </td>
                                @foreach ($plans as $plan)
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if ($currentSubscription && $currentSubscription->plan_id === $plan->id)
                                            <span
                                                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                Paket Saat Ini
                                            </span>
                                        @else
                                            <a href="https://wa.me/6281529211963?text=Halo,%20saya%20ingin%20berlangganan%20paket%20{{ urlencode($plan->name) }}"
                                                target="_blank"
                                                class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-green-700">
                                                Berlangganan
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 rounded-lg bg-blue-50 p-4 ring-1 ring-blue-600/20">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="size-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ms-3">
                            <h3 class="text-sm font-medium text-blue-800">Butuh bantuan memilih paket?</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>
                                    Hubungi kami via WhatsApp untuk mendiskusikan kebutuhan Anda dan mendapatkan rekomendasi
                                    yang sesuai.
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="https://wa.me/6281529211963?text=Halo,%20saya%20butuh%20bantuan%20memilih%20paket%20langganan"
                                    target="_blank"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                    <svg class="-ms-0.5 me-1.5 size-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                    Hubungi Kami
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
