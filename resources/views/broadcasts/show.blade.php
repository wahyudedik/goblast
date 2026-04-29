@extends('layouts.app')

@section('page-title', 'Detail Broadcast')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <a href="{{ route('broadcasts.index') }}"
                class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="-ms-1 me-1 size-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"
                        clip-rule="evenodd" />
                </svg>
                Kembali ke Broadcast
            </a>
            <div class="mt-2 sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $broadcast->name }}</h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Dibuat {{ $broadcast->created_at->diffForHumans() }}
                    </p>
                </div>
                <div class="mt-4 flex gap-2 sm:mt-0">
                    @if ($broadcast->status === 'draft')
                        <form action="{{ route('broadcasts.dispatch', $broadcast) }}" method="POST"
                            data-confirm="Apakah Anda yakin ingin mulai mengirim broadcast ini?"
                            data-confirm-title="Mulai Broadcast" data-confirm-button="Ya, Mulai" data-confirm-type="info">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                                <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.925A1.5 1.5 0 005.135 9.25h6.115a.75.75 0 010 1.5H5.135a1.5 1.5 0 00-1.442 1.086l-1.414 4.926a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.115A28.897 28.897 0 003.105 2.289z" />
                                </svg>
                                Mulai Kirim
                            </button>
                        </form>
                    @endif

                    @if (in_array($broadcast->status, ['queued', 'running']))
                        <form action="{{ route('broadcasts.cancel', $broadcast) }}" method="POST"
                            data-confirm="Apakah Anda yakin ingin membatalkan broadcast ini? Pesan yang tertunda tidak akan dikirim."
                            data-confirm-title="Batalkan Broadcast" data-confirm-button="Ya, Batalkan"
                            data-confirm-type="warning">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                                <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                                Batalkan Broadcast
                            </button>
                        </form>
                    @endif

                    @if ($broadcast->failed_count > 0)
                        <form action="{{ route('broadcasts.retry-failed', $broadcast) }}" method="POST"
                            data-confirm="Apakah Anda yakin ingin mencoba ulang semua pesan yang gagal?"
                            data-confirm-title="Coba Ulang Pesan Gagal" data-confirm-button="Ya, Coba Ulang"
                            data-confirm-type="info">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z"
                                        clip-rule="evenodd" />
                                </svg>
                                Coba Ulang Gagal
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Manual Message (if no template) -->
        @if ($broadcast->message && !$broadcast->template)
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Pesan</h3>
                    <div class="mt-4 rounded-lg bg-gray-50 p-4">
                        <p class="whitespace-pre-wrap text-sm text-gray-900">{{ $broadcast->message }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Status and Progress -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Broadcast Info -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Informasi Broadcast</h3>
                    <dl class="mt-4 space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd class="text-sm font-medium">
                                @if ($broadcast->status === 'completed')
                                    <span
                                        class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                        Completed
                                    </span>
                                @elseif ($broadcast->status === 'running')
                                    <span
                                        class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                        <svg class="-ms-0.5 me-1.5 size-2 fill-blue-500 animate-pulse" viewBox="0 0 6 6">
                                            <circle cx="3" cy="3" r="3" />
                                        </svg>
                                        Running
                                    </span>
                                @elseif ($broadcast->status === 'queued')
                                    <span
                                        class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                        Queued
                                    </span>
                                @elseif ($broadcast->status === 'draft')
                                    <span
                                        class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        Draft
                                    </span>
                                @elseif ($broadcast->status === 'cancelled')
                                    <span
                                        class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                        Cancelled
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                        Failed
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Device</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $broadcast->device->name }}
                            </dd>
                        </div>
                        @if ($broadcast->template)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Template</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ $broadcast->template->name }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Sumber</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                {{ ucfirst($broadcast->source_type) }}</dd>
                        </div>
                        @if ($broadcast->scheduled_at)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Dijadwalkan</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ $broadcast->scheduled_at->format('d M Y H:i') }}</dd>
                            </div>
                        @endif
                        @if ($broadcast->started_at)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Dimulai</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ $broadcast->started_at->format('Y-m-d H:i:s') }}</dd>
                            </div>
                        @endif
                        @if ($broadcast->completed_at)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Selesai</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ $broadcast->completed_at->format('Y-m-d H:i:s') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Progress Stats -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Progres</h3>
                    <div class="mt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Progres Keseluruhan</span>
                            <span class="text-sm font-medium text-gray-900" id="progress-percentage">
                                {{ number_format($progress->percentage, 1) }}%
                            </span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full bg-green-600 transition-all duration-500" id="progress-bar"
                                style="width: {{ $progress->percentage }}%"></div>
                        </div>
                    </div>

                    <dl class="mt-6 grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-gray-50 p-4">
                            <dt class="text-sm text-gray-500">Total</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900" id="stat-total">
                                {{ $progress->total }}</dd>
                        </div>
                        <div class="rounded-lg bg-green-50 p-4">
                            <dt class="text-sm text-green-700">Terkirim</dt>
                            <dd class="mt-1 text-2xl font-semibold text-green-900" id="stat-sent">
                                {{ $progress->sent }}</dd>
                        </div>
                        <div class="rounded-lg bg-red-50 p-4">
                            <dt class="text-sm text-red-700">Gagal</dt>
                            <dd class="mt-1 text-2xl font-semibold text-red-900" id="stat-failed">
                                {{ $progress->failed }}</dd>
                        </div>
                        <div class="rounded-lg bg-yellow-50 p-4">
                            <dt class="text-sm text-yellow-700">Tertunda</dt>
                            <dd class="mt-1 text-2xl font-semibold text-yellow-900" id="stat-pending">
                                {{ $progress->pending }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Message Logs -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900">Log Pesan</h3>

                @if ($messageLogs->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">Belum ada pesan.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="py-3.5 pe-3 ps-0 text-left text-sm font-semibold text-gray-900">
                                        Penerima</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Status</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Dikirim Pada</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Error</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($messageLogs as $log)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pe-3 ps-0 text-sm font-medium text-gray-900">
                                            {{ $log->recipient }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            @if ($log->status === 'sent')
                                                <span
                                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                    Terkirim
                                                </span>
                                            @elseif ($log->status === 'failed')
                                                <span
                                                    class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                                    Gagal
                                                </span>
                                            @elseif ($log->status === 'retrying')
                                                <span
                                                    class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                                    Mencoba Ulang
                                                </span>
                                            @elseif ($log->status === 'cancelled')
                                                <span
                                                    class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                                    Dibatalkan
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                                    Tertunda
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            @if ($log->sent_at)
                                                {{ $log->sent_at->format('Y-m-d H:i:s') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500">
                                            @if ($log->error_message)
                                                <span class="text-red-600" title="{{ $log->error_message }}">
                                                    {{ Str::limit($log->error_message, 50) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($messageLogs->hasPages())
                        <div class="mt-6">
                            {{ $messageLogs->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if (in_array($broadcast->status, ['queued', 'running']))
        @push('scripts')
            <script>
                // Poll for progress updates every 5 seconds
                const broadcastId = {{ $broadcast->id }};
                const progressUrl = '{{ route('broadcasts.progress', $broadcast) }}';

                function updateProgress() {
                    fetch(progressUrl)
                        .then(response => response.json())
                        .then(data => {
                            // Update progress bar
                            document.getElementById('progress-bar').style.width = data.percentage + '%';
                            document.getElementById('progress-percentage').textContent = data.percentage.toFixed(1) + '%';

                            // Update stats
                            document.getElementById('stat-total').textContent = data.total;
                            document.getElementById('stat-sent').textContent = data.sent;
                            document.getElementById('stat-failed').textContent = data.failed;
                            document.getElementById('stat-pending').textContent = data.pending;

                            // Reload page if status changed to completed/cancelled/failed
                            if (!['queued', 'running'].includes(data.status)) {
                                window.location.reload();
                            }
                        })
                        .catch(error => console.error('Error fetching progress:', error));
                }

                // Update every 5 seconds
                setInterval(updateProgress, 5000);
            </script>
        @endpush
    @endif
@endsection
