@extends('layouts.app')

@section('page-title', 'Edit Kontak')

@section('content')
    <div class="mb-6">
        <a href="{{ route('contacts.index') }}"
            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Kontak
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Edit Kontak</h2>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('contacts.update', $contact) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="phone_number" class="block text-sm font-semibold text-gray-900 mb-2">Nomor Telepon <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="phone_number" id="phone_number"
                        value="{{ old('phone_number', $contact->phone_number) }}" required
                        class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('phone_number') border-red-500 @else border-gray-300 @enderror">
                    @error('phone_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Nama</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $contact->name) }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                </div>
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $contact->email) }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                </div>
                <div>
                    <label for="group" class="block text-sm font-semibold text-gray-900 mb-2">Grup</label>
                    <input type="text" name="group" id="group" value="{{ old('group', $contact->group) }}"
                        list="group-list"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                    <datalist id="group-list">
                        @foreach ($groups as $g)
                            <option value="{{ $g }}">
                        @endforeach
                    </datalist>
                </div>
            </div>
            <div>
                <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Catatan</label>
                <textarea name="notes" id="notes" rows="3"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">{{ old('notes', $contact->notes) }}</textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('contacts.index') }}"
                    class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">Batal</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all shadow-sm">Simpan
                    Perubahan</button>
            </div>
        </form>
    </div>
@endsection
