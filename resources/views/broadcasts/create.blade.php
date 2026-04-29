@extends('layouts.app')

@section('page-title', 'Buat Broadcast')

@section('content')
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('broadcasts.index') }}"
                    class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                        </path>
                    </svg>
                    Kembali ke Broadcasts
                </a>
                <h2 class="text-2xl font-bold text-gray-900">Buat Broadcast Baru</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Kirim pesan ke banyak penerima sekaligus.
                </p>
            </div>
        </div>
    </div>

    <!-- Quota Warning -->
    @if (!$isUnlimited && $remainingQuota < 100)
        <div class="mb-6 rounded-lg border-2 border-yellow-200 bg-yellow-50 p-4">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-yellow-900 mb-1">Peringatan Kuota Rendah</h3>
                    <p class="text-sm text-yellow-800">
                        Anda memiliki {{ $remainingQuota }} pesan tersisa dalam kuota Anda. Pastikan Anda memiliki cukup
                        kuota untuk broadcast Anda.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('broadcasts.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6"
            id="broadcast-form">
            @csrf

            <!-- Broadcast Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">
                    Nama Broadcast <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    placeholder="Contoh: Promo Akhir Tahun, Pengumuman Produk Baru"
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
            </div>

            <!-- Device Selection -->
            <div>
                <label for="device_id" class="block text-sm font-semibold text-gray-900 mb-2">
                    Device <span class="text-red-500">*</span>
                </label>
                @if ($devices->isEmpty())
                    <div class="rounded-lg border-2 border-yellow-200 bg-yellow-50 p-4">
                        <p class="text-sm text-yellow-800">
                            Tidak ada device yang terhubung. Silakan <a href="{{ route('devices.index') }}"
                                class="font-semibold underline hover:text-yellow-900">hubungkan device</a> terlebih dahulu.
                        </p>
                    </div>
                @else
                    <select name="device_id" id="device_id" required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('device_id') border-red-500 @else border-gray-300 @enderror">
                        <option value="">Pilih device</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}" {{ old('device_id') == $device->id ? 'selected' : '' }}>
                                {{ $device->name }} ({{ $device->phone_number }})
                            </option>
                        @endforeach
                    </select>
                    @error('device_id')
                        <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <!-- Template Selection -->
            <div>
                <label for="template_id" class="block text-sm font-semibold text-gray-900 mb-2">
                    Template (Opsional)
                </label>
                <select name="template_id" id="template_id"
                    class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('template_id') border-red-500 @else border-gray-300 @enderror">
                    <option value="">Tanpa template (tulis pesan manual)</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }} ({{ ucfirst($template->type) }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-sm text-gray-600">
                    Pilih template atau tulis pesan manual di bawah.
                </p>
                @error('template_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Manual Message (shown when no template selected) -->
            <div id="manual-message-section">
                <label for="message" class="block text-sm font-semibold text-gray-900 mb-2">
                    Pesan <span class="text-red-500" id="message-required">*</span>
                </label>
                <textarea name="message" id="message" rows="6" placeholder="Tulis pesan broadcast Anda di sini..."
                    class="w-full px-4 py-3 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm @error('message') border-red-500 @else border-gray-300 @enderror">{{ old('message') }}</textarea>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <p class="text-gray-600">Tulis pesan yang akan dikirim ke semua penerima.</p>
                    <p class="text-gray-600"><span id="msg-char-count" class="font-semibold">0</span> / 4096</p>
                </div>
                @error('message')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Schedule (Optional) -->
            <div>
                <label for="scheduled_at" class="block text-sm font-semibold text-gray-900 mb-2">
                    Jadwalkan Pengiriman (Opsional)
                </label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}"
                    class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('scheduled_at') border-red-500 @else border-gray-300 @enderror">
                <p class="mt-2 text-sm text-gray-600">
                    Kosongkan untuk mengirim segera, atau pilih tanggal dan waktu untuk menjadwalkan pengiriman.
                </p>
                @error('scheduled_at')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Recipient Source -->
    </div>

    <!-- Recipient Source -->
    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-3">
            Sumber Penerima <span class="text-red-500">*</span>
        </label>
        <div class="space-y-3">
            <label
                class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-green-300 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                <input id="source_contacts" name="source_type" type="radio" value="contacts"
                    {{ old('source_type', 'contacts') === 'contacts' ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                <div class="ml-3 flex-1">
                    <span class="block text-sm font-semibold text-gray-900">📇 Dari Kontak</span>
                    <span class="block text-sm text-gray-600 mt-0.5">Pilih dari daftar kontak atau load per grup</span>
                </div>
            </label>
            <label
                class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-green-300 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                <input id="source_csv" name="source_type" type="radio" value="csv"
                    {{ old('source_type') === 'csv' ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                <div class="ml-3 flex-1">
                    <span class="block text-sm font-semibold text-gray-900">📄 Upload File CSV</span>
                    <span class="block text-sm text-gray-600 mt-0.5">Upload file CSV dengan daftar nomor telepon</span>
                </div>
            </label>
            <label
                class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-green-300 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                <input id="source_database" name="source_type" type="radio" value="database"
                    {{ old('source_type') === 'database' ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                <div class="ml-3 flex-1">
                    <span class="block text-sm font-semibold text-gray-900">✏️ Input Manual</span>
                    <span class="block text-sm text-gray-600 mt-0.5">Masukkan nomor telepon secara manual</span>
                </div>
            </label>
        </div>
    </div>

    <!-- Contacts Section -->
    <div id="contacts-section" class="space-y-4">
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-semibold text-gray-900">Pilih Kontak</label>
                <a href="{{ route('contacts.index') }}" target="_blank"
                    class="text-xs text-green-600 hover:text-green-700">Kelola Kontak →</a>
            </div>
            <!-- Group Filter Buttons -->
            <div class="flex gap-2 flex-wrap mb-3">
                <button type="button" data-group=""
                    class="contact-group-btn active px-3 py-1.5 text-xs font-medium rounded-full border-2 border-green-500 bg-green-50 text-green-700 hover:bg-green-100 transition-colors">
                    Semua
                </button>
                @foreach ($contactGroups as $group)
                    <button type="button" data-group="{{ $group }}"
                        class="contact-group-btn px-3 py-1.5 text-xs font-medium rounded-full border-2 border-gray-300 text-gray-600 hover:border-green-300 hover:bg-green-50 transition-colors">
                        {{ $group }}
                    </button>
                @endforeach
            </div>
            <!-- Contact List -->
            <div id="contacts-list" class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto bg-white">
                <div class="p-4 text-center text-sm text-gray-500">Memuat kontak...</div>
            </div>
            <input type="hidden" name="contact_recipients" id="contact_recipients" value="">
            <div class="mt-2 flex items-center justify-between text-sm">
                <div class="flex gap-3">
                    <button type="button" onclick="selectAllContacts(true)"
                        class="text-green-600 hover:text-green-700 font-medium">Pilih Semua</button>
                    <button type="button" onclick="selectAllContacts(false)"
                        class="text-gray-500 hover:text-gray-700 font-medium">Hapus Semua</button>
                </div>
                <p class="text-gray-600">Terpilih: <span id="selected-contacts-count"
                        class="font-semibold text-green-600">0</span></p>
            </div>
        </div>
    </div>

    <!-- CSV Upload Section -->
    <div id="csv-section" class="space-y-4">
        <div>
            <label for="csv_file" class="block text-sm font-semibold text-gray-900 mb-2">
                File CSV <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                    class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 @error('csv_file') border-red-500 @else border-gray-300 @enderror">
            </div>
            <p class="mt-2 text-sm text-gray-600">
                Upload file CSV dengan nomor telepon. Ukuran maksimal: 5MB. Format: satu nomor per baris (contoh:
                6281234567890).
            </p>
            @error('csv_file')
                <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- CSV Format Info -->
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
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">Format CSV</h3>
                    <p class="text-sm text-blue-800 mb-2">
                        File CSV Anda harus berisi nomor telepon dalam format internasional (dimulai dengan 62),
                        satu per baris:
                    </p>
                    <pre class="rounded-lg bg-blue-100 p-3 font-mono text-xs text-blue-900">6281234567890
6281234567891
6281234567892</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Entry Section -->
    <div id="manual-section" class="hidden space-y-4">
        <div>
            <label for="recipients_text" class="block text-sm font-semibold text-gray-900 mb-2">
                Nomor Telepon <span class="text-red-500">*</span>
            </label>
            <textarea name="recipients_text" id="recipients_text" rows="8"
                placeholder="Masukkan nomor telepon, satu per baris&#10;Contoh:&#10;6281234567890&#10;6281234567891&#10;6281234567892"
                class="w-full px-4 py-3 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm @error('recipients_text') border-red-500 @else border-gray-300 @enderror">{{ old('recipients_text') }}</textarea>
            <div class="mt-2 flex items-center justify-between text-sm">
                <p class="text-gray-600">
                    Masukkan nomor telepon dalam format internasional (dimulai dengan 62), satu per baris.
                </p>
                <p class="text-gray-600">
                    Jumlah penerima: <span id="recipients-count" class="font-semibold text-green-600">0</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    <div id="preview-section" class="hidden">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Preview</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Penerima</p>
                    <p id="preview-total" class="text-2xl font-bold text-gray-900">0</p>
                </div>
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Nomor Valid</p>
                    <p id="preview-valid" class="text-2xl font-bold text-green-600">0</p>
                </div>
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Nomor Invalid</p>
                    <p id="preview-invalid" class="text-2xl font-bold text-red-600">0</p>
                </div>
                @if (!$isUnlimited)
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Sisa Kuota</p>
                        <p id="preview-quota" class="text-2xl font-bold text-blue-600">{{ $remainingQuota }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
        <a href="{{ route('broadcasts.index') }}"
            class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
            Batal
        </a>
        <button type="submit" id="submit-btn" {{ $devices->isEmpty() ? 'disabled' : '' }}
            class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-green-600">
            Buat Broadcast
        </button>
    </div>
    </form>
    </div>

    @push('scripts')
        <script>
            const sourceContacts = document.getElementById('source_contacts');
            const sourceCSV = document.getElementById('source_csv');
            const sourceDatabase = document.getElementById('source_database');
            const contactsSection = document.getElementById('contacts-section');
            const csvSection = document.getElementById('csv-section');
            const manualSection = document.getElementById('manual-section');
            const recipientsText = document.getElementById('recipients_text');
            const recipientsCount = document.getElementById('recipients-count');
            const previewSection = document.getElementById('preview-section');
            const form = document.getElementById('broadcast-form');
            const contactRecipientsInput = document.getElementById('contact_recipients');

            let allContacts = [];
            let selectedPhones = new Set();

            function toggleSections() {
                contactsSection.classList.add('hidden');
                csvSection.classList.add('hidden');
                manualSection.classList.add('hidden');
                previewSection.classList.add('hidden');

                if (sourceContacts.checked) {
                    contactsSection.classList.remove('hidden');
                    loadContacts('');
                } else if (sourceCSV.checked) {
                    csvSection.classList.remove('hidden');
                } else {
                    manualSection.classList.remove('hidden');
                    updateRecipientsCount();
                }
            }

            // Load contacts from API
            async function loadContacts(group) {
                const list = document.getElementById('contacts-list');
                list.innerHTML =
                    '<div class="p-4 text-center text-sm text-gray-500"><svg class="animate-spin h-5 w-5 text-green-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Memuat...</div>';

                // Update active group button
                document.querySelectorAll('.contact-group-btn').forEach(btn => {
                    if (btn.dataset.group === group) {
                        btn.classList.add('border-green-500', 'bg-green-50', 'text-green-700', 'active');
                        btn.classList.remove('border-gray-300', 'text-gray-600');
                    } else {
                        btn.classList.remove('border-green-500', 'bg-green-50', 'text-green-700', 'active');
                        btn.classList.add('border-gray-300', 'text-gray-600');
                    }
                });

                try {
                    const url = new URL('{{ route('api.contacts.search') }}', window.location.origin);
                    if (group) url.searchParams.set('group', group);

                    const response = await fetch(url);
                    allContacts = await response.json();

                    if (allContacts.length === 0) {
                        list.innerHTML =
                            '<div class="p-4 text-center text-sm text-gray-500">Tidak ada kontak. <a href="{{ route('contacts.create') }}" class="text-green-600 hover:text-green-700 font-medium">Tambah kontak</a></div>';
                        return;
                    }

                    list.innerHTML = allContacts.map(c => `
                        <label class="flex items-center px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                            <input type="checkbox" value="${c.phone_number}" onchange="toggleContact(this)"
                                ${selectedPhones.has(c.phone_number) ? 'checked' : ''}
                                class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <div class="ml-3 flex-1 min-w-0">
                                <span class="block text-sm font-medium text-gray-900 truncate">${c.name || c.phone_number}</span>
                                <span class="block text-xs text-gray-500 font-mono">${c.phone_number}${c.group ? ' · ' + c.group : ''}</span>
                            </div>
                        </label>
                    `).join('');
                } catch (e) {
                    list.innerHTML = '<div class="p-4 text-center text-sm text-red-500">Gagal memuat kontak</div>';
                }
            }

            function toggleContact(checkbox) {
                if (checkbox.checked) {
                    selectedPhones.add(checkbox.value);
                } else {
                    selectedPhones.delete(checkbox.value);
                }
                updateContactCount();
            }

            function selectAllContacts(select) {
                document.querySelectorAll('#contacts-list input[type="checkbox"]').forEach(cb => {
                    cb.checked = select;
                    if (select) selectedPhones.add(cb.value);
                    else selectedPhones.delete(cb.value);
                });
                updateContactCount();
            }

            function updateContactCount() {
                document.getElementById('selected-contacts-count').textContent = selectedPhones.size;
                contactRecipientsInput.value = Array.from(selectedPhones).join(',');
            }

            // Group button click handlers
            document.querySelectorAll('.contact-group-btn').forEach(btn => {
                btn.addEventListener('click', () => loadContacts(btn.dataset.group));
            });

            function updateRecipientsCount() {
                const text = recipientsText.value.trim();
                if (!text) {
                    recipientsCount.textContent = '0';
                    previewSection.classList.add('hidden');
                    return;
                }

                const lines = text.split('\n').filter(line => line.trim());
                const phoneRegex = /^62[0-9]{9,13}$/;
                const valid = lines.filter(line => phoneRegex.test(line.trim())).length;
                const invalid = lines.length - valid;

                recipientsCount.textContent = lines.length;
                document.getElementById('preview-total').textContent = lines.length;
                document.getElementById('preview-valid').textContent = valid;
                document.getElementById('preview-invalid').textContent = invalid;

                previewSection.classList.remove('hidden');
            }

            sourceContacts.addEventListener('change', toggleSections);
            sourceCSV.addEventListener('change', toggleSections);
            sourceDatabase.addEventListener('change', toggleSections);
            recipientsText.addEventListener('input', updateRecipientsCount);

            // Convert textarea to array before submit
            form.addEventListener('submit', function(e) {
                if (sourceDatabase.checked) {
                    const text = recipientsText.value.trim();
                    const lines = text.split('\n')
                        .map(line => line.trim())
                        .filter(line => line);

                    lines.forEach((phone, index) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `recipients[${index}]`;
                        input.value = phone;
                        form.appendChild(input);
                    });
                }
            });

            toggleSections();

            // Template / manual message toggle
            const templateSelect = document.getElementById('template_id');
            const manualMessageSection = document.getElementById('manual-message-section');
            const messageTextarea = document.getElementById('message');
            const messageRequired = document.getElementById('message-required');
            const msgCharCount = document.getElementById('msg-char-count');

            function toggleMessageSection() {
                if (templateSelect.value) {
                    manualMessageSection.classList.add('hidden');
                    messageTextarea.removeAttribute('required');
                    messageRequired.classList.add('hidden');
                } else {
                    manualMessageSection.classList.remove('hidden');
                    messageTextarea.setAttribute('required', 'required');
                    messageRequired.classList.remove('hidden');
                }
            }

            templateSelect.addEventListener('change', toggleMessageSection);
            toggleMessageSection();

            if (messageTextarea) {
                messageTextarea.addEventListener('input', function() {
                    msgCharCount.textContent = this.value.length;
                });
                msgCharCount.textContent = messageTextarea.value.length;
            }
        </script>
    @endpush
@endsection
