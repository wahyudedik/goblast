@extends('layouts.app')

@section('page-title', 'Paket Langganan')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Pilih Paket Anda</h1>
            <p class="mt-4 text-lg text-gray-600">
                Pilih paket yang sesuai dengan kebutuhan bisnis Anda. Semua paket sudah termasuk fitur utama.
            </p>
        </div>

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <div
                    class="relative flex flex-col overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-900/5 {{ $loop->index === 1 ? 'lg:scale-105 lg:z-10' : '' }}">
                    @if ($loop->index === 1)
                        <div class="absolute inset-x-0 top-0 h-1 bg-green-600"></div>
                    @endif

                    <div class="p-6">
                        <!-- Plan Name -->
                        <h2 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h2>

                        <!-- Plan Description -->
                        @if ($plan->description)
                            <p class="mt-2 text-sm text-gray-600">{{ $plan->description }}</p>
                        @endif

                        <!-- Price -->
                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span class="text-4xl font-bold text-gray-900">
                                    Rp {{ number_format($plan->price, 0, ',', '.') }}
                                </span>
                                <span class="ms-2 text-sm text-gray-500">/bulan</span>
                            </div>
                        </div>

                        <!-- Subscribe Button -->
                        <div class="mt-6">
                            <a href="https://wa.me/6281529211963?text=Halo,%20saya%20ingin%20berlangganan%20paket%20{{ urlencode($plan->name) }}"
                                target="_blank"
                                class="block w-full rounded-md {{ $loop->index === 1 ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-900 hover:bg-gray-800' }} px-3 py-2 text-center text-sm font-semibold text-white shadow-sm">
                                Berlangganan
                            </a>
                        </div>

                        <!-- Features List -->
                        <div class="mt-8">
                            <h3 class="text-sm font-semibold text-gray-900">Yang termasuk:</h3>
                            <ul class="mt-4 space-y-3">
                                <!-- Message Quota -->
                                <li class="flex items-start">
                                    <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="ms-3 text-sm text-gray-700">
                                        @if ($plan->message_quota === null)
                                            <strong>Tidak terbatas</strong> pesan per bulan
                                        @else
                                            <strong>{{ number_format($plan->message_quota) }}</strong> pesan per bulan
                                        @endif
                                    </span>
                                </li>

                                <!-- Devices -->
                                <li class="flex items-start">
                                    <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="ms-3 text-sm text-gray-700">
                                        @if ($plan->has_multi_device)
                                            <strong>Multi</strong> device WhatsApp
                                        @else
                                            <strong>{{ $plan->max_devices }}</strong> device WhatsApp
                                        @endif
                                    </span>
                                </li>

                                <!-- Broadcast -->
                                <li class="flex items-start">
                                    <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="ms-3 text-sm text-gray-700">
                                        Broadcast pesan
                                    </span>
                                </li>

                                <!-- Templates -->
                                <li class="flex items-start">
                                    <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="ms-3 text-sm text-gray-700">
                                        Template pesan
                                    </span>
                                </li>

                                <!-- Auto Reply -->
                                <li class="flex items-start">
                                    <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="ms-3 text-sm text-gray-700">
                                        Auto-reply dengan kata kunci
                                    </span>
                                </li>

                                <!-- Message Logs -->
                                <li class="flex items-start">
                                    <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="ms-3 text-sm text-gray-700">
                                        Log pesan & laporan
                                    </span>
                                </li>

                                <!-- Reminder -->
                                <li class="flex items-start">
                                    @if ($plan->has_reminder)
                                        <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="ms-3 text-sm text-gray-700">
                                            Pengingat terjadwal
                                        </span>
                                    @else
                                        <svg class="size-5 shrink-0 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                        <span class="ms-3 text-sm text-gray-400 line-through">
                                            Pengingat terjadwal
                                        </span>
                                    @endif
                                </li>

                                <!-- API Access -->
                                <li class="flex items-start">
                                    @if ($plan->has_api)
                                        <svg class="size-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="ms-3 text-sm text-gray-700">
                                            Akses API untuk integrasi
                                        </span>
                                    @else
                                        <svg class="size-5 shrink-0 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                        <span class="ms-3 text-sm text-gray-400 line-through">
                                            Akses API untuk integrasi
                                        </span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Contact Information -->
        <div class="rounded-lg bg-gray-50 p-6 text-center">
            <h3 class="text-lg font-semibold text-gray-900">Butuh Paket Custom?</h3>
            <p class="mt-2 text-sm text-gray-600">
                Hubungi kami via WhatsApp untuk mendiskusikan harga dan fitur khusus sesuai kebutuhan Anda.
            </p>
            <div class="mt-4">
                <a href="https://wa.me/6281529211963?text=Halo,%20saya%20ingin%20bertanya%20tentang%20paket%20custom"
                    target="_blank"
                    class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                    <svg class="-ms-0.5 me-1.5 size-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                    </svg>
                    Hubungi Kami via WhatsApp
                </a>
            </div>
        </div>

        <!-- Back to Subscription -->
        <div class="text-center">
            <a href="{{ route('subscription.index') }}" class="text-sm font-medium text-green-600 hover:text-green-700">
                ← Kembali ke Langganan
            </a>
        </div>
    </div>
@endsection
