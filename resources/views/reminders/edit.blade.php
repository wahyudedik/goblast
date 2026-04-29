@extends('layouts.app')

@section('page-title', 'Edit Reminder')

@section('content')
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('reminders.show', $reminder) }}"
            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Reminder
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Edit Reminder</h2>
        <p class="mt-1 text-sm text-gray-600">Perbarui pengaturan reminder.</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('reminders.update', $reminder) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Reminder Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Nama Reminder <span
                        class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $reminder->name) }}" required
                    placeholder="Contoh: Pengingat SPP Bulanan, Reminder Invoice Mingguan"
                    class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('name') border-red-500 @else border-gray-300 @enderror">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type & Frequency Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Reminder Type -->
                <div>
                    <label for="type" class="block text-sm font-semibold text-gray-900 mb-2">Tipe <span
                            class="text-red-500">*</span></label>
                    <select name="type" id="type" required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('type') border-red-500 @else border-gray-300 @enderror">
                        <option value="">Pilih tipe</option>
                        <option value="spp_due" {{ old('type', $reminder->type) === 'spp_due' ? 'selected' : '' }}>⏰ SPP
                            Jatuh Tempo</option>
                        <option value="invoice_unpaid"
                            {{ old('type', $reminder->type) === 'invoice_unpaid' ? 'selected' : '' }}>📄 Invoice Belum Bayar
                        </option>
                        <option value="booking_tomorrow"
                            {{ old('type', $reminder->type) === 'booking_tomorrow' ? 'selected' : '' }}>📅 Booking Besok
                        </option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Frequency -->
                <div>
                    <label for="frequency" class="block text-sm font-semibold text-gray-900 mb-2">Frekuensi <span
                            class="text-red-500">*</span></label>
                    <select name="frequency" id="frequency" required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('frequency') border-red-500 @else border-gray-300 @enderror">
                        <option value="daily" {{ old('frequency', $reminder->frequency) === 'daily' ? 'selected' : '' }}>🔄
                            Setiap Hari</option>
                        <option value="weekly" {{ old('frequency', $reminder->frequency) === 'weekly' ? 'selected' : '' }}>
                            📅 Setiap Minggu</option>
                        <option value="monthly"
                            {{ old('frequency', $reminder->frequency) === 'monthly' ? 'selected' : '' }}>🗓️ Setiap Bulan
                        </option>
                        <option value="yearly" {{ old('frequency', $reminder->frequency) === 'yearly' ? 'selected' : '' }}>
                            📆 Setiap Tahun</option>
                    </select>
                    @error('frequency')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Time & Day Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Send Time -->
                <div>
                    <label for="send_time" class="block text-sm font-semibold text-gray-900 mb-2">Jam Kirim <span
                            class="text-red-500">*</span></label>
                    <input type="time" name="send_time" id="send_time"
                        value="{{ old('send_time', $reminder->send_time ? \Carbon\Carbon::parse($reminder->send_time)->format('H:i') : '08:00') }}"
                        required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('send_time') border-red-500 @else border-gray-300 @enderror">
                    <p class="mt-2 text-sm text-gray-600">Waktu pengiriman (WIB)</p>
                    @error('send_time')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Send Day (for weekly/monthly) -->
                <div id="send-day-section">
                    <label for="send_day" class="block text-sm font-semibold text-gray-900 mb-2">
                        <span id="send-day-label">Hari Kirim</span>
                    </label>
                    <select name="send_day" id="send_day"
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors border-gray-300">
                        <!-- Options populated by JS -->
                    </select>
                    <p id="send-day-help" class="mt-2 text-sm text-gray-600"></p>
                    @error('send_day')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Device Selection -->
            <div>
                <label for="device_id" class="block text-sm font-semibold text-gray-900 mb-2">Device <span
                        class="text-red-500">*</span></label>
                @if ($devices->isEmpty())
                    <div class="rounded-lg border-2 border-yellow-200 bg-yellow-50 p-4">
                        <p class="text-sm text-yellow-800">Tidak ada device yang terhubung. <a
                                href="{{ route('devices.index') }}" class="font-semibold underline">Hubungkan device</a>
                            terlebih dahulu.</p>
                    </div>
                @else
                    <select name="device_id" id="device_id" required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('device_id') border-red-500 @else border-gray-300 @enderror">
                        <option value="">Pilih device</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}"
                                {{ old('device_id', $reminder->device_id) == $device->id ? 'selected' : '' }}>
                                {{ $device->name }} ({{ $device->phone_number }})</option>
                        @endforeach
                    </select>
                    @error('device_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <!-- Template or Manual Message -->
            <div>
                <label for="template_id" class="block text-sm font-semibold text-gray-900 mb-2">Template (Opsional)</label>
                <select name="template_id" id="template_id"
                    class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors border-gray-300">
                    <option value="">Tanpa template (tulis pesan manual)</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}"
                            {{ old('template_id', $reminder->template_id) == $template->id ? 'selected' : '' }}>
                            {{ $template->name }} ({{ ucfirst($template->type) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Manual Message -->
            <div id="manual-message-section">
                <label for="message" class="block text-sm font-semibold text-gray-900 mb-2">Pesan <span
                        class="text-red-500" id="msg-required">*</span></label>
                <textarea name="message" id="message" rows="5"
                    placeholder="Tulis pesan reminder Anda di sini...&#10;Gunakan variabel: {nama}, {jumlah}, {tanggal}"
                    class="w-full px-4 py-3 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm @error('message') border-red-500 @else border-gray-300 @enderror">{{ old('message', $reminder->message) }}</textarea>
                @error('message')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Recipients -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="recipients_text" class="block text-sm font-semibold text-gray-900">Penerima <span
                            class="text-red-500">*</span></label>
                    <button type="button" onclick="openContactPicker()"
                        class="text-xs font-medium text-green-600 hover:text-green-700 flex items-center gap-1">
                        📇 Load dari Kontak
                    </button>
                </div>
                <textarea name="recipients_text" id="recipients_text" rows="5" required
                    placeholder="Masukkan nomor telepon, satu per baris&#10;Contoh:&#10;6281234567890&#10;6281234567891"
                    class="w-full px-4 py-3 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm @error('recipients_text') border-red-500 @else border-gray-300 @enderror">{{ old('recipients_text', is_array($reminder->recipients) ? implode("\n", $reminder->recipients) : $reminder->recipients) }}</textarea>
                <p class="mt-2 text-sm text-gray-600">Nomor telepon format internasional (62xxx), satu per baris.</p>
                @error('recipients_text')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Schedule Summary -->
            <div class="rounded-lg border-2 border-green-200 bg-green-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-green-900 mb-1">Ringkasan Jadwal</h3>
                        <p id="schedule-summary" class="text-sm text-green-800">Pesan akan dikirim setiap hari pukul 08:00
                            WIB</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('reminders.show', $reminder) }}"
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

    @push('scripts')
        <script>
            const frequencySelect = document.getElementById('frequency');
            const sendDaySection = document.getElementById('send-day-section');
            const sendDaySelect = document.getElementById('send_day');
            const sendDayLabel = document.getElementById('send-day-label');
            const sendDayHelp = document.getElementById('send-day-help');
            const sendTimeInput = document.getElementById('send_time');
            const scheduleSummary = document.getElementById('schedule-summary');
            const templateSelect = document.getElementById('template_id');
            const manualMessageSection = document.getElementById('manual-message-section');
            const msgRequired = document.getElementById('msg-required');
            const messageTextarea = document.getElementById('message');

            const existingSendDay = {{ old('send_day', $reminder->send_day ?? 'null') }};

            const dayNames = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
                'Oktober', 'November', 'Desember'
            ];

            function updateDayOptions() {
                const freq = frequencySelect.value;
                sendDaySelect.innerHTML = '';

                if (freq === 'daily') {
                    sendDaySection.classList.add('hidden');
                } else if (freq === 'weekly') {
                    sendDaySection.classList.remove('hidden');
                    sendDayLabel.textContent = 'Hari Kirim';
                    sendDayHelp.textContent = 'Pilih hari dalam seminggu';
                    for (let i = 1; i <= 7; i++) {
                        const opt = document.createElement('option');
                        opt.value = i;
                        opt.textContent = dayNames[i];
                        if (existingSendDay === i) opt.selected = true;
                        sendDaySelect.appendChild(opt);
                    }
                } else if (freq === 'monthly') {
                    sendDaySection.classList.remove('hidden');
                    sendDayLabel.textContent = 'Tanggal Kirim';
                    sendDayHelp.textContent = 'Pilih tanggal dalam sebulan (1-28)';
                    for (let i = 1; i <= 28; i++) {
                        const opt = document.createElement('option');
                        opt.value = i;
                        opt.textContent = 'Tanggal ' + i;
                        if (existingSendDay === i) opt.selected = true;
                        sendDaySelect.appendChild(opt);
                    }
                } else if (freq === 'yearly') {
                    sendDaySection.classList.remove('hidden');
                    sendDayLabel.textContent = 'Bulan Kirim';
                    sendDayHelp.textContent = 'Pilih bulan dalam setahun';
                    for (let i = 1; i <= 12; i++) {
                        const opt = document.createElement('option');
                        opt.value = i;
                        opt.textContent = monthNames[i];
                        if (existingSendDay === i) opt.selected = true;
                        sendDaySelect.appendChild(opt);
                    }
                }

                updateSummary();
            }

            function updateSummary() {
                const freq = frequencySelect.value;
                const time = sendTimeInput.value || '08:00';
                const day = sendDaySelect.value;
                let text = '';

                if (freq === 'daily') {
                    text = `Pesan akan dikirim setiap hari pukul ${time} WIB`;
                } else if (freq === 'weekly') {
                    text = `Pesan akan dikirim setiap hari ${dayNames[day] || '...'} pukul ${time} WIB`;
                } else if (freq === 'monthly') {
                    text = `Pesan akan dikirim setiap tanggal ${day || '...'} pukul ${time} WIB`;
                } else if (freq === 'yearly') {
                    text = `Pesan akan dikirim setiap bulan ${monthNames[day] || '...'} pukul ${time} WIB`;
                }

                scheduleSummary.textContent = text;
            }

            function toggleMessage() {
                if (templateSelect.value) {
                    manualMessageSection.classList.add('hidden');
                    messageTextarea.removeAttribute('required');
                    msgRequired.classList.add('hidden');
                } else {
                    manualMessageSection.classList.remove('hidden');
                    messageTextarea.setAttribute('required', 'required');
                    msgRequired.classList.remove('hidden');
                }
            }

            frequencySelect.addEventListener('change', updateDayOptions);
            sendDaySelect.addEventListener('change', updateSummary);
            sendTimeInput.addEventListener('change', updateSummary);
            templateSelect.addEventListener('change', toggleMessage);

            updateDayOptions();
            toggleMessage();

            // Contact picker functions
            let modalSelectedPhones = new Set();

            window.openContactPicker = async function() {
                document.getElementById('contact-picker-modal').classList.remove('hidden');
                modalSelectedPhones = new Set();
                await loadModalContacts('');
            };

            window.closeContactPicker = function() {
                document.getElementById('contact-picker-modal').classList.add('hidden');
            };

            async function loadModalContacts(group) {
                const list = document.getElementById('modal-contacts-list');
                list.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Memuat...</div>';
                try {
                    const url = new URL('{{ route('api.contacts.search') }}', window.location.origin);
                    if (group) url.searchParams.set('group', group);
                    const res = await fetch(url);
                    const contacts = await res.json();
                    if (contacts.length === 0) {
                        list.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Tidak ada kontak</div>';
                        return;
                    }
                    list.innerHTML = contacts.map(c => `
                        <label class="flex items-center px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                            <input type="checkbox" value="${c.phone_number}" onchange="modalToggle(this)" ${modalSelectedPhones.has(c.phone_number) ? 'checked' : ''}
                                class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <div class="ml-3"><span class="text-sm font-medium text-gray-900">${c.name || c.phone_number}</span>
                            <span class="text-xs text-gray-500 ml-2 font-mono">${c.phone_number}</span></div>
                        </label>`).join('');
                } catch (e) {
                    list.innerHTML = '<div class="p-4 text-center text-sm text-red-500">Gagal memuat</div>';
                }
            }

            window.modalToggle = function(cb) {
                if (cb.checked) modalSelectedPhones.add(cb.value);
                else modalSelectedPhones.delete(cb.value);
                document.getElementById('modal-count').textContent = modalSelectedPhones.size;
            };

            window.modalSelectAll = function(select) {
                document.querySelectorAll('#modal-contacts-list input[type="checkbox"]').forEach(cb => {
                    cb.checked = select;
                    if (select) modalSelectedPhones.add(cb.value);
                    else modalSelectedPhones.delete(cb.value);
                });
                document.getElementById('modal-count').textContent = modalSelectedPhones.size;
            };

            window.applyContacts = function() {
                const textarea = document.getElementById('recipients_text');
                const existing = new Set(textarea.value.split('\n').map(l => l.trim()).filter(l => l));
                modalSelectedPhones.forEach(p => existing.add(p));
                textarea.value = Array.from(existing).join('\n');
                window.closeContactPicker();
            };
        </script>
    @endpush

    <!-- Contact Picker Modal -->
    <div id="contact-picker-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeContactPicker()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pilih Kontak</h3>
                    <div id="modal-contacts-list"
                        class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto bg-white">
                        <div class="p-4 text-center text-sm text-gray-500">Memuat kontak...</div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <button type="button" onclick="modalSelectAll(true)"
                            class="text-green-600 hover:text-green-700 font-medium">Pilih Semua</button>
                        <span class="text-gray-600">Terpilih: <span id="modal-count"
                                class="font-semibold text-green-600">0</span></span>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" onclick="closeContactPicker()"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50">Batal</button>
                    <button type="button" onclick="applyContacts()"
                        class="px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700">Tambahkan</button>
                </div>
            </div>
        </div>
    </div>
@endsection
