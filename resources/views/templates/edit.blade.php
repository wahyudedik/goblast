@extends('layouts.app')

@section('page-title', 'Edit Template')

@section('content')
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('templates.show', $template) }}"
                    class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                        </path>
                    </svg>
                    Kembali ke Template
                </a>
                <h2 class="text-2xl font-bold text-gray-900">Edit Template</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Perbarui template pesan Anda.
                </p>
            </div>
        </div>
    </div>

    <!-- Usage Warning -->
    @if ($template->broadcasts_count > 0 || $template->reminders_count > 0)
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
                    <h3 class="text-sm font-semibold text-yellow-900 mb-1">Template Sedang Digunakan</h3>
                    <p class="text-sm text-yellow-800">
                        Template ini sedang digunakan oleh
                        @if ($template->broadcasts_count > 0)
                            <strong>{{ $template->broadcasts_count }}</strong>
                            broadcast{{ $template->broadcasts_count > 1 ? 's' : '' }}
                        @endif
                        @if ($template->broadcasts_count > 0 && $template->reminders_count > 0)
                            dan
                        @endif
                        @if ($template->reminders_count > 0)
                            <strong>{{ $template->reminders_count }}</strong>
                            reminder{{ $template->reminders_count > 1 ? 's' : '' }}
                        @endif.
                        Perubahan akan mempengaruhi pesan di masa mendatang.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('templates.update', $template) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Template Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">
                    Nama Template <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required
                    placeholder="Contoh: Welcome Message, Promo Diskon, dll"
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

            <!-- Template Type -->
            <div>
                <label for="type" class="block text-sm font-semibold text-gray-900 mb-2">
                    Tipe Template <span class="text-red-500">*</span>
                </label>
                <select name="type" id="type" required
                    class="w-full px-4 py-2.5 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors @error('type') border-red-500 @else border-gray-300 @enderror">
                    <option value="">Pilih tipe template</option>
                    <option value="notification" {{ old('type', $template->type) === 'notification' ? 'selected' : '' }}>
                        📢 Notification - Pemberitahuan umum
                    </option>
                    <option value="promo" {{ old('type', $template->type) === 'promo' ? 'selected' : '' }}>
                        🎉 Promo - Penawaran dan diskon
                    </option>
                    <option value="reminder" {{ old('type', $template->type) === 'reminder' ? 'selected' : '' }}>
                        ⏰ Reminder - Pengingat pembayaran
                    </option>
                </select>
                @error('type')
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

            <!-- Template Content -->
            <div>
                <label for="content" class="block text-sm font-semibold text-gray-900 mb-2">
                    Konten Template <span class="text-red-500">*</span>
                </label>
                <textarea name="content" id="content" rows="8" required
                    placeholder="Tulis pesan template Anda di sini...&#10;&#10;Gunakan variabel dengan format: {name}, {company}, dll"
                    class="w-full px-4 py-3 border rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors font-mono text-sm @error('content') border-red-500 @else border-gray-300 @enderror">{{ old('content', $template->content) }}</textarea>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <p class="text-gray-600">
                        Gunakan variabel: <code
                            class="bg-gray-100 px-2 py-0.5 rounded text-xs font-mono">{variable_name}</code>
                    </p>
                    <p class="text-gray-600">
                        <span id="char-count" class="font-semibold">0</span> / 4096 karakter
                    </p>
                </div>
                @error('content')
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

            <!-- Preview -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">
                    Preview
                </label>
                <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 max-w-md">
                        <div class="flex items-start gap-3">
                            <div class="shrink-0">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p id="preview" class="text-sm text-gray-900 whitespace-pre-wrap break-words">
                                    Ketik konten template untuk melihat preview...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Variabel terdeteksi: <span id="variables-list" class="font-mono font-semibold text-green-600">Tidak
                        ada</span>
                </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('templates.show', $template) }}"
                    class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                    Update Template
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            const contentTextarea = document.getElementById('content');
            const previewDiv = document.getElementById('preview');
            const charCountSpan = document.getElementById('char-count');
            const variablesListSpan = document.getElementById('variables-list');

            function updatePreview() {
                const content = contentTextarea.value;
                const charCount = content.length;

                // Update character count
                charCountSpan.textContent = charCount;
                if (charCount > 4096) {
                    charCountSpan.classList.add('text-red-600', 'font-bold');
                } else {
                    charCountSpan.classList.remove('text-red-600', 'font-bold');
                }

                // Update preview with sample data
                let preview = content;
                const variables = content.match(/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/g);

                if (variables) {
                    const uniqueVars = [...new Set(variables)];
                    variables.forEach(variable => {
                        const varName = variable.slice(1, -1);
                        preview = preview.replace(new RegExp('\\{' + varName + '\\}', 'g'),
                            `<span class="bg-green-100 text-green-800 px-1.5 py-0.5 rounded font-semibold">${varName}</span>`
                        );
                    });

                    variablesListSpan.innerHTML = uniqueVars.map(v =>
                        `<code class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">${v}</code>`
                    ).join(', ');
                } else {
                    variablesListSpan.textContent = 'Tidak ada';
                }

                previewDiv.innerHTML = preview || 'Ketik konten template untuk melihat preview...';
            }

            contentTextarea.addEventListener('input', updatePreview);
            updatePreview();
        </script>
    @endpush
@endsection
