<!-- Confirmation Modal Component -->
<div x-data="{ show: false, title: '', message: '', confirmText: 'Konfirmasi', cancelText: 'Batal', onConfirm: null, type: 'danger' }" x-show="show"
    x-on:open-confirm-modal.window="
        show = true;
        title = $event.detail.title;
        message = $event.detail.message;
        confirmText = $event.detail.confirmText || 'Konfirmasi';
        cancelText = $event.detail.cancelText || 'Batal';
        onConfirm = $event.detail.onConfirm;
        type = $event.detail.type || 'danger';
    "
    x-on:keydown.escape.window="show = false" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

    <!-- Backdrop -->
    <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="show = false">
    </div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="show" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden" @click.stop>

            <!-- Icon & Content -->
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="shrink-0">
                        <div x-show="type === 'danger'"
                            class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div x-show="type === 'warning'"
                            class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div x-show="type === 'info'"
                            class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div x-show="type === 'success'"
                            class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Text Content -->
                    <div class="flex-1 min-w-0">
                        <h3 x-text="title" class="text-lg font-semibold text-gray-900 mb-2"></h3>
                        <p x-text="message" class="text-sm text-gray-600"></p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                <button type="button" @click="show = false"
                    class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                    <span x-text="cancelText"></span>
                </button>
                <button type="button" @click="if (onConfirm) onConfirm(); show = false"
                    :class="{
                        'bg-red-600 hover:bg-red-700 focus:ring-red-500': type === 'danger',
                        'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500': type === 'warning',
                        'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500': type === 'info',
                        'bg-green-600 hover:bg-green-700 focus:ring-green-500': type === 'success'
                    }"
                    class="px-6 py-2.5 border border-transparent rounded-lg font-semibold text-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                    <span x-text="confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>
