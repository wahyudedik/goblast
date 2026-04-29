@extends('layouts.app')

@section('page-title', 'Edit Auto Reply')

@section('content')
    <div class="mb-6">
        <a href="{{ route('auto-reply.index') }}"
            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Auto Reply
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Edit Auto Reply</h2>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('auto-reply.update', $rule) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="keyword" class="block text-sm font-semibold text-gray-900 mb-2">Kata Kunci <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="keyword" id="keyword" value="{{ old('keyword', $rule->keyword) }}"
                        required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('keyword') border-red-500 @else border-gray-300 @enderror">
                    @error('keyword')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="device_id" class="block text-sm font-semibold text-gray-900 mb-2">Device <span
                            class="text-red-500">*</span></label>
                    <select name="device_id" id="device_id" required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('device_id') border-red-500 @else border-gray-300 @enderror">
                        <option value="">Pilih device</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}"
                                {{ old('device_id', $rule->device_id) == $device->id ? 'selected' : '' }}>
                                {{ $device->name }} ({{ $device->phone_number }})</option>
                        @endforeach
                    </select>
                    @error('device_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="reply" class="block text-sm font-semibold text-gray-900 mb-2">Pesan Balasan <span
                        class="text-red-500">*</span></label>
                <textarea name="reply" id="reply" rows="5" required
                    class="w-full px-4 py-3 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm @error('reply') border-red-500 @else border-gray-300 @enderror">{{ old('reply', $rule->reply) }}</textarea>
                @error('reply')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="priority" class="block text-sm font-semibold text-gray-900 mb-2">Prioritas</label>
                <input type="number" name="priority" id="priority" value="{{ old('priority', $rule->priority) }}"
                    min="0" max="999"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('auto-reply.index') }}"
                    class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">Batal</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all shadow-sm">Simpan
                    Perubahan</button>
            </div>
        </form>
    </div>
@endsection
