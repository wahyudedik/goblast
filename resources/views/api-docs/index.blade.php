@extends('layouts.app')

@section('page-title', 'Dokumentasi API')

@section('content')
    <div class="space-y-8">

        {{-- ============================================================== --}}
        {{-- HEADER — Title, version badge, base URL                        --}}
        {{-- ============================================================== --}}
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900">Dokumentasi API</h1>
                        <span
                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">v1</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Referensi lengkap untuk mengintegrasikan aplikasi Anda dengan API
                        Konektivitas.</p>
                </div>
            </div>

            <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                <p class="text-sm text-gray-600">
                    <span class="font-medium text-gray-900">Base URL:</span>
                    <code
                        class="ml-2 rounded bg-gray-200 px-2 py-0.5 text-sm font-mono text-gray-800">{{ $baseUrl }}</code>
                </p>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- TABLE OF CONTENTS                                              --}}
        {{-- ============================================================== --}}
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Daftar Isi</h2>
            <nav class="grid gap-2 sm:grid-cols-2">
                <a href="#autentikasi"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition">
                    <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Autentikasi
                </a>
                <a href="#send-message"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition">
                    <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Endpoint: Send Message
                </a>
                <a href="#send-bulk"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition">
                    <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Endpoint: Send Bulk
                </a>
                <a href="#message-status"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition">
                    <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Endpoint: Message Status
                </a>
                <a href="#error-handling"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition">
                    <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Penanganan Error
                </a>
                <a href="#rate-limiting"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition">
                    <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Rate Limiting
                </a>
                <a href="#contoh-kode"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition sm:col-span-2">
                    <svg class="h-4 w-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    Contoh Kode
                </a>
            </nav>
        </div>

        {{-- ============================================================== --}}
        {{-- AUTHENTICATION SECTION                                         --}}
        {{-- ============================================================== --}}
        <div id="autentikasi" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Autentikasi</h2>
            <p class="text-sm text-gray-600 mb-4">
                Semua request API harus menyertakan token autentikasi di header <code
                    class="rounded bg-gray-100 px-1.5 py-0.5 text-sm font-mono text-gray-800">Authorization</code>. Gunakan
                skema <strong>Bearer Token</strong> untuk mengautentikasi setiap request.
            </p>

            <p class="text-sm font-medium text-gray-700 mb-2">Format Header:</p>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm"><code>Authorization: Bearer {your_api_token}</code></pre>

            <div class="mt-4">
                <p class="text-sm text-gray-600">
                    Anda dapat membuat dan mengelola token API di halaman
                    <a href="{{ route('api-tokens.index') }}" class="text-green-600 hover:text-green-700 font-medium">Token
                        API</a>.
                </p>
            </div>

            {{-- Security notice --}}
            <div class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Peringatan Keamanan</h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            Jaga kerahasiaan token API Anda. Jangan pernah membagikan token secara publik atau menyimpannya
                            di repositori kode. Jika token Anda terkompromi, segera cabut token tersebut dan buat token
                            baru.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- ENDPOINT: SEND MESSAGE                                         --}}
        {{-- ============================================================== --}}
        <div id="send-message" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-4">
                <span
                    class="inline-flex items-center rounded-md bg-green-600 px-2.5 py-1 text-xs font-bold text-white">POST</span>
                <code class="text-sm font-mono text-gray-800">/api/v1/send-message</code>
            </div>
            <p class="text-sm text-gray-600 mb-6">Mengirim pesan WhatsApp ke satu nomor penerima.</p>

            {{-- Parameters table --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Parameter Request</h3>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Parameter</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Tipe</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Wajib</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">device_id</td>
                            <td class="px-4 py-3 text-sm text-gray-600">integer</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Ya</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">ID device WhatsApp yang akan digunakan untuk
                                mengirim pesan</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">to</td>
                            <td class="px-4 py-3 text-sm text-gray-600">string</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Ya</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Nomor telepon penerima dalam format internasional
                                (contoh: 6281234567890)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">message</td>
                            <td class="px-4 py-3 text-sm text-gray-600">string</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700">Kondisional</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Isi pesan yang akan dikirim. Wajib jika <code
                                    class="rounded bg-gray-100 px-1 py-0.5 text-xs font-mono">template_id</code> tidak
                                disertakan. Maksimal 4096 karakter.</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">template_id</td>
                            <td class="px-4 py-3 text-sm text-gray-600">integer</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Opsional</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">ID template pesan yang akan digunakan. Jika
                                disertakan, isi template akan digunakan sebagai pesan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Validation rules --}}
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Aturan Validasi</h4>
                <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
                    <li><code class="rounded bg-blue-100 px-1 py-0.5 text-xs font-mono">to</code> harus dalam format nomor
                        telepon internasional (contoh: 6281234567890)</li>
                    <li><code class="rounded bg-blue-100 px-1 py-0.5 text-xs font-mono">message</code> wajib diisi jika
                        <code class="rounded bg-blue-100 px-1 py-0.5 text-xs font-mono">template_id</code> tidak disertakan
                    </li>
                    <li><code class="rounded bg-blue-100 px-1 py-0.5 text-xs font-mono">message</code> maksimal 4096
                        karakter</li>
                </ul>
            </div>

            {{-- Sample request --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Request</h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-6"><code>{
    "device_id": 1,
    "to": "6281234567890",
    "message": "Halo, ini pesan dari API Konektivitas!"
}</code></pre>

            {{-- Sample response --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Response <span class="text-green-600">(HTTP
                    202)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm"><code>{
    "success": true,
    "message": "Message queued for sending",
    "data": {
        "job_id": "msg_abc123def456",
        "status": "pending",
        "recipient": "6281234567890"
    }
}</code></pre>
        </div>

        {{-- ============================================================== --}}
        {{-- ENDPOINT: SEND BULK                                            --}}
        {{-- ============================================================== --}}
        <div id="send-bulk" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-4">
                <span
                    class="inline-flex items-center rounded-md bg-green-600 px-2.5 py-1 text-xs font-bold text-white">POST</span>
                <code class="text-sm font-mono text-gray-800">/api/v1/send-bulk</code>
            </div>
            <p class="text-sm text-gray-600 mb-6">Mengirim pesan WhatsApp ke banyak penerima sekaligus (broadcast).</p>

            {{-- Parameters table --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Parameter Request</h3>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Parameter</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Tipe</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Wajib</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">device_id</td>
                            <td class="px-4 py-3 text-sm text-gray-600">integer</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Ya</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">ID device WhatsApp yang akan digunakan untuk
                                mengirim pesan</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">recipients</td>
                            <td class="px-4 py-3 text-sm text-gray-600">array</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Ya</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Array nomor telepon penerima dalam format
                                internasional. Minimal 1, maksimal 10.000 nomor.</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">message</td>
                            <td class="px-4 py-3 text-sm text-gray-600">string</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700">Kondisional</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Isi pesan yang akan dikirim ke semua penerima.
                                Wajib jika <code
                                    class="rounded bg-gray-100 px-1 py-0.5 text-xs font-mono">template_id</code> tidak
                                disertakan.</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">template_id</td>
                            <td class="px-4 py-3 text-sm text-gray-600">integer</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Opsional</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">ID template pesan yang akan digunakan. Jika
                                disertakan, isi template akan digunakan sebagai pesan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Recipient limits note --}}
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-1">Batas Penerima</h4>
                <p class="text-sm text-blue-700">Array <code
                        class="rounded bg-blue-100 px-1 py-0.5 text-xs font-mono">recipients</code> menerima minimal 1 dan
                    maksimal 10.000 nomor telepon per request.</p>
            </div>

            {{-- Sample request --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Request</h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-6"><code>{
    "device_id": 1,
    "recipients": [
        "6281234567890",
        "6289876543210",
        "6282345678901"
    ],
    "message": "Halo, ini pesan broadcast dari API Konektivitas!"
}</code></pre>

            {{-- Sample response --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Response <span class="text-green-600">(HTTP
                    202)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm"><code>{
    "success": true,
    "message": "Bulk message queued for sending",
    "data": {
        "job_id": "bulk_xyz789ghi012",
        "status": "pending",
        "total_recipients": 3
    }
}</code></pre>
        </div>

        {{-- ============================================================== --}}
        {{-- ENDPOINT: MESSAGE STATUS                                       --}}
        {{-- ============================================================== --}}
        <div id="message-status" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-4">
                <span
                    class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1 text-xs font-bold text-white">GET</span>
                <code class="text-sm font-mono text-gray-800">/api/v1/message-status/{jobId}</code>
            </div>
            <p class="text-sm text-gray-600 mb-6">Memeriksa status pengiriman pesan berdasarkan Job ID yang diperoleh dari
                response send-message atau send-bulk.</p>

            {{-- Path parameter --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Parameter Path</h3>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Parameter</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Tipe</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Wajib</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">jobId</td>
                            <td class="px-4 py-3 text-sm text-gray-600">string</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Ya</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Job ID yang diperoleh dari response endpoint
                                send-message atau send-bulk</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Sample response --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Response <span class="text-green-600">(HTTP
                    200)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-6"><code>{
    "success": true,
    "data": {
        "job_id": "msg_abc123def456",
        "status": "sent",
        "recipient": "6281234567890",
        "sent_at": "2024-01-15T10:30:00Z",
        "created_at": "2024-01-15T10:29:55Z"
    }
}</code></pre>

            {{-- Status values table --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Nilai Status</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">pending</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Pesan sedang menunggu dalam antrian untuk dikirim
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">sent</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Pesan berhasil dikirim ke penerima</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">failed</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Pengiriman pesan gagal</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">cancelled</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Pengiriman pesan dibatalkan</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700">retrying</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Pengiriman pesan sedang dicoba ulang setelah
                                kegagalan sebelumnya</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- ERROR HANDLING SECTION                                         --}}
        {{-- ============================================================== --}}
        <div id="error-handling" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Penanganan Error</h2>
            <p class="text-sm text-gray-600 mb-6">API menggunakan kode status HTTP standar untuk menunjukkan keberhasilan
                atau kegagalan request.</p>

            {{-- HTTP status codes table --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Kode Status HTTP</h3>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">200</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">OK — Request berhasil diproses</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">202</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Accepted — Pesan diterima dan masuk antrian
                                pengiriman</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">401</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Unauthorized — Token API tidak valid atau tidak
                                disertakan</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">403</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Forbidden — Akses ditolak (langganan tidak aktif
                                atau fitur tidak tersedia)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">404</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Not Found — Resource yang diminta tidak ditemukan
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">422</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Unprocessable Entity — Validasi gagal atau kuota
                                terlampaui</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700">429</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Too Many Requests — Batas rate limit terlampaui
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Common error scenarios --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Skenario Error Umum</h3>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Skenario</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Kode HTTP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">Token API tidak valid atau sudah dicabut</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">401</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">Langganan sudah kedaluwarsa</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">403</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">Device tidak terhubung</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">422</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">Format nomor telepon tidak valid</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">422</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">Kuota pesan terlampaui</td>
                            <td class="px-4 py-3"><span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">422</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Sample error responses --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Error: Authentication Error <span
                    class="text-red-600">(401)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-4"><code>{
    "success": false,
    "message": "Unauthenticated. Invalid or missing API token."
}</code></pre>

            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Error: Validation Error <span
                    class="text-red-600">(422)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-4"><code>{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "to": ["The to field must be a valid phone number in international format."],
        "message": ["The message field is required when template_id is not present."]
    }
}</code></pre>

            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Error: Quota Exceeded <span
                    class="text-red-600">(422)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-4"><code>{
    "success": false,
    "message": "Monthly message quota exceeded. Please upgrade your plan."
}</code></pre>

            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Error: Forbidden <span
                    class="text-red-600">(403)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm"><code>{
    "success": false,
    "message": "Your subscription has expired. Please renew to continue using the API."
}</code></pre>
        </div>

        {{-- ============================================================== --}}
        {{-- RATE LIMITING SECTION                                          --}}
        {{-- ============================================================== --}}
        <div id="rate-limiting" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Rate Limiting</h2>
            <p class="text-sm text-gray-600 mb-4">
                API membatasi jumlah request untuk menjaga stabilitas layanan. Setiap token API dibatasi <strong>60 request
                    per menit</strong>.
            </p>

            {{-- Rate limit headers --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Response Headers</h3>
            <p class="text-sm text-gray-600 mb-3">Setiap response API menyertakan header berikut untuk membantu Anda
                memantau penggunaan rate limit:</p>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Header</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">X-RateLimit-Limit</td>
                            <td class="px-4 py-3 text-sm text-gray-600">Jumlah maksimum request yang diizinkan per menit
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">X-RateLimit-Remaining</td>
                            <td class="px-4 py-3 text-sm text-gray-600">Jumlah request yang tersisa dalam periode saat ini
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">X-RateLimit-Reset</td>
                            <td class="px-4 py-3 text-sm text-gray-600">Waktu (Unix timestamp) ketika rate limit akan
                                direset</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- 429 sample response --}}
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Contoh Response Rate Limit Exceeded <span
                    class="text-red-600">(HTTP 429)</span></h3>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm"><code>{
    "success": false,
    "message": "Too many requests. Please wait before sending another request.",
    "retry_after": 30
}</code></pre>
        </div>

        {{-- ============================================================== --}}
        {{-- CODE EXAMPLES SECTION                                          --}}
        {{-- ============================================================== --}}
        <div id="contoh-kode" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Contoh Kode</h2>

            {{-- ==================== SEND MESSAGE EXAMPLES ==================== --}}
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Kirim Pesan (Send Message)</h3>

            {{-- PHP (Guzzle) --}}
            <h4 class="text-sm font-semibold text-gray-700 mb-2">PHP (Guzzle)</h4>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-4"><code>&lt;?php

use GuzzleHttp\Client;

$client = new Client();

$response = $client-&gt;post('{{ $baseUrl }}/send-message', [
    'headers' =&gt; [
        'Authorization' =&gt; 'Bearer YOUR_API_TOKEN',
        'Accept'        =&gt; 'application/json',
        'Content-Type'  =&gt; 'application/json',
    ],
    'json' =&gt; [
        'device_id' =&gt; 1,
        'to'        =&gt; '6281234567890',
        'message'   =&gt; 'Halo dari API!',
    ],
]);

$body = json_decode($response-&gt;getBody(), true);
echo $body['data']['job_id'];</code></pre>

            {{-- JavaScript (Fetch) --}}
            <h4 class="text-sm font-semibold text-gray-700 mb-2">JavaScript (Fetch)</h4>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-4"><code>const response = await fetch('{{ $baseUrl }}/send-message', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer YOUR_API_TOKEN',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        device_id: 1,
        to: '6281234567890',
        message: 'Halo dari API!',
    }),
});

const data = await response.json();
console.log(data.data.job_id);</code></pre>

            {{-- cURL --}}
            <h4 class="text-sm font-semibold text-gray-700 mb-2">cURL</h4>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-8"><code>curl -X POST {{ $baseUrl }}/send-message \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": 1,
    "to": "6281234567890",
    "message": "Halo dari API!"
  }'</code></pre>

            {{-- ==================== MESSAGE STATUS EXAMPLES ==================== --}}
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Cek Status Pesan (Message Status)</h3>

            {{-- PHP (Guzzle) --}}
            <h4 class="text-sm font-semibold text-gray-700 mb-2">PHP (Guzzle)</h4>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-4"><code>&lt;?php

use GuzzleHttp\Client;

$client = new Client();
$jobId = 'msg_abc123def456';

$response = $client-&gt;get('{{ $baseUrl }}/message-status/' . $jobId, [
    'headers' =&gt; [
        'Authorization' =&gt; 'Bearer YOUR_API_TOKEN',
        'Accept'        =&gt; 'application/json',
    ],
]);

$body = json_decode($response-&gt;getBody(), true);
echo $body['data']['status'];</code></pre>

            {{-- JavaScript (Fetch) --}}
            <h4 class="text-sm font-semibold text-gray-700 mb-2">JavaScript (Fetch)</h4>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm mb-4"><code>const jobId = 'msg_abc123def456';

const response = await fetch(`{{ $baseUrl }}/message-status/${jobId}`, {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer YOUR_API_TOKEN',
        'Accept': 'application/json',
    },
});

const data = await response.json();
console.log(data.data.status);</code></pre>

            {{-- cURL --}}
            <h4 class="text-sm font-semibold text-gray-700 mb-2">cURL</h4>
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm"><code>curl -X GET {{ $baseUrl }}/message-status/msg_abc123def456 \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code></pre>
        </div>

    </div>
@endsection
