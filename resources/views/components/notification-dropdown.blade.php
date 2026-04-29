<!-- Notification Dropdown Component -->
<div x-data="{
    open: false,
    notifications: [],
    unreadCount: 0,
    loading: false,

    async fetchNotifications() {
        this.loading = true;
        try {
            const response = await fetch('/api/notifications');
            const data = await response.json();
            this.notifications = data.notifications || [];
            this.unreadCount = data.unread_count || 0;
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        } finally {
            this.loading = false;
        }
    },

    async markAsRead(notificationId) {
        try {
            await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Content-Type': 'application/json'
                }
            });

            // Update local state
            const notification = this.notifications.find(n => n.id === notificationId);
            if (notification && !notification.read_at) {
                notification.read_at = new Date().toISOString();
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    },

    async markAllAsRead() {
        try {
            await fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Content-Type': 'application/json'
                }
            });

            // Update local state
            this.notifications.forEach(n => {
                if (!n.read_at) n.read_at = new Date().toISOString();
            });
            this.unreadCount = 0;
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }
}" x-init="fetchNotifications()" @click.away="open = false" class="relative">

    <!-- Notification Button -->
    <button type="button" @click="open = !open; if (open && unreadCount > 0) markAllAsRead()"
        class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 transition-colors">
        <span class="sr-only">View notifications</span>

        <!-- Bell Icon -->
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>

        <!-- Unread Badge -->
        <span x-show="unreadCount > 0" x-text="unreadCount > 99 ? '99+' : unreadCount"
            class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-white text-xs font-medium flex items-center justify-center min-w-[20px]">
        </span>
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        style="display: none;">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Notifikasi</h3>
                <button @click="markAllAsRead()" x-show="unreadCount > 0"
                    class="text-xs text-green-600 hover:text-green-700 font-medium">
                    Tandai Semua Dibaca
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <!-- Loading State -->
            <div x-show="loading" class="px-4 py-8 text-center">
                <svg class="animate-spin h-6 w-6 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">Memuat notifikasi...</p>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && notifications.length === 0" class="px-4 py-8 text-center">
                <svg class="h-12 w-12 text-gray-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M15 17h5l-5 5v-5zM9 17H4l5 5v-5zM12 3v18" />
                </svg>
                <p class="mt-2 text-sm text-gray-500">Tidak ada notifikasi</p>
            </div>

            <!-- Notifications -->
            <template x-for="notification in notifications" :key="notification.id">
                <div @click="markAsRead(notification.id)" :class="notification.read_at ? 'bg-white' : 'bg-blue-50'"
                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors">

                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="shrink-0 mt-0.5">
                            <!-- Success Icon -->
                            <div x-show="notification.type === 'success'"
                                class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>

                            <!-- Warning Icon -->
                            <div x-show="notification.type === 'warning'"
                                class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>

                            <!-- Error Icon -->
                            <div x-show="notification.type === 'error'"
                                class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>

                            <!-- Info Icon (default) -->
                            <div x-show="!['success', 'warning', 'error'].includes(notification.type)"
                                class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900" x-text="notification.title"></p>
                            <p class="text-sm text-gray-600 mt-1" x-text="notification.message"></p>
                            <p class="text-xs text-gray-400 mt-2" x-text="notification.created_at_human"></p>
                        </div>

                        <!-- Unread Indicator -->
                        <div x-show="!notification.read_at" class="shrink-0 mt-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <a href="{{ route('notifications.index') }}"
                class="block text-center text-sm text-green-600 hover:text-green-700 font-medium">
                Lihat Semua Notifikasi
            </a>
        </div>
    </div>
</div>
