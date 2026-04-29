@extends('layouts.app')

@section('page-title', 'Tambah Auto Reply')

@section('content')
    <div class="mb-6">
        <a href="{{ route('auto-reply.index') }}"
            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Auto Reply
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Tambah Auto Reply</h2>
        <p class="mt-1 text-sm text-gray-600">Buat balasan otomatis berdasarkan kata kunci tertentu.</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('auto-reply.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Keyword -->
                <div>
                    <label for="keyword" class="block text-sm font-semibold text-gray-900 mb-2">Kata Kunci <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="keyword" id="keyword" value="{{ old('keyword') }}" required
                        placeholder="Contoh: harga, info, promo, daftar"
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('keyword') border-red-500 @else border-gray-300 @enderror">
                    <p class="mt-2 text-sm text-gray-600">Pesan yang mengandung kata ini akan dibalas otomatis.</p>
                    @error('keyword')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Device -->
                <div>
                    <label for="device_id" class="block text-sm font-semibold text-gray-900 mb-2">Device <span
                            class="text-red-500">*</span></label>
                    @if ($devices->isEmpty())
                        <div class="rounded-lg border-2 border-yellow-200 bg-yellow-50 p-4">
                            <p class="text-sm text-yellow-800">Tidak ada device terhubung. <a
                                    href="{{ route('devices.index') }}" class="font-semibold underline">Hubungkan device</a>
                                dulu.</p>
                        </div>
                    @else
                        <select name="device_id" id="device_id" required
                            class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('device_id') border-red-500 @else border-gray-300 @enderror">
                            <option value="">Pilih device</option>
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}" {{ old('device_id') == $device->id ? 'selected' : '' }}>
                                    {{ $device->name }} ({{ $device->phone_number }})</option>
                            @endforeach
                        </select>
                        @error('device_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>

            <!-- Reply Message -->
            <div>
                <label for="reply" class="block text-sm font-semibold text-gray-900 mb-2">Pesan Balasan <span
                        class="text-red-500">*</span></label>
                <textarea name="reply" id="reply" rows="5" required
                    placeholder="Tulis pesan balasan otomatis di sini...&#10;&#10;Contoh: Terima kasih telah menghubungi kami! Untuk info harga, silakan kunjungi website kami di example.com"
                    class="w-full px-4 py-3 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm @error('reply') border-red-500 @else border-gray-300 @enderror">{{ old('reply') }}</textarea>
                @error('reply')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-semibold text-gray-900 mb-2">Prioritas</label>
                <input type="number" name="priority" id="priority" value="{{ old('priority', 0) }}" min="0"
                    max="999"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                <p class="mt-2 text-sm text-gray-600">Angka lebih kecil = prioritas lebih tinggi. Jika pesan cocok dengan
                    beberapa keyword, yang prioritas tertinggi akan digunakan.</p>
            </div>

            <!-- Preview -->
            <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Preview</h3>
                <div class="flex gap-4">
                    <div class="flex-1 bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-xs text-gray-500 mb-1">Pesan masuk mengandung:</p>
                        <p class="text-sm font-mono font-semibold text-blue-600" id="preview-keyword">keyword</p>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                    <div class="flex-1 bg-green-50 rounded-lg p-3 border border-green-200">
                        <p class="text-xs text-gray-500 mb-1">Auto reply:</p>
                        <p class="text-sm text-gray-900 whitespace-pre-wrap" id="preview-reply">Pesan balasan...</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('auto-reply.index') }}"
                    class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">Batal</a>
                <button type="submit" {{ $devices->isEmpty() ? 'disabled' : '' }}
                    class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Simpan Auto Reply
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            const keywordInput = document.getElementById('keyword');
            const replyInput = document.getElementById('reply');
            const previewKeyword = document.getElementById('preview-keyword');
            const previewReply = document.getElementById('preview-reply');

            keywordInput.addEventListener('input', () => {
                previewKeyword.textContent = keywordInput.value || 'keyword';
            });
            replyInput.addEventListener('input', () => {
                previewReply.textContent = replyInput.value || 'Pesan balasan...';
            });
        </script>
    @endpush
@endsection
