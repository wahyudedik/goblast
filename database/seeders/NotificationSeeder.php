<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create notifications for all admin users
        $users = User::where('role', 'admin')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No admin users found, skipping notification seeder.');

            return;
        }

        $notifications = [
            [
                'type' => 'success',
                'title' => 'Broadcast Berhasil Dikirim',
                'message' => 'Broadcast "Promo Akhir Tahun" telah berhasil dikirim ke 150 penerima.',
                'action_url' => '/broadcasts',
                'action_text' => 'Lihat Broadcast',
            ],
            [
                'type' => 'warning',
                'title' => 'Kuota Hampir Habis',
                'message' => 'Kuota pesan Anda tersisa 50 dari 1000. Pertimbangkan untuk upgrade paket.',
                'action_url' => '/subscription',
                'action_text' => 'Upgrade Paket',
            ],
            [
                'type' => 'info',
                'title' => 'Device Baru Terhubung',
                'message' => 'Device "Customer Service 2" berhasil terhubung dan siap digunakan.',
                'action_url' => '/devices',
                'action_text' => 'Lihat Devices',
            ],
            [
                'type' => 'error',
                'title' => 'Gagal Mengirim Pesan',
                'message' => '5 pesan gagal dikirim karena nomor tidak valid. Periksa daftar penerima.',
                'action_url' => '/message-logs',
                'action_text' => 'Lihat Log',
            ],
            [
                'type' => 'info',
                'title' => 'Template Baru Dibuat',
                'message' => 'Template "Welcome Message v2" berhasil dibuat dan siap digunakan.',
                'action_url' => '/templates',
                'action_text' => 'Lihat Templates',
            ],
            [
                'type' => 'success',
                'title' => 'Pembayaran Berhasil',
                'message' => 'Pembayaran langganan bulan ini telah berhasil diproses.',
                'action_url' => '/subscription',
                'action_text' => 'Lihat Subscription',
            ],
            [
                'type' => 'warning',
                'title' => 'Device Terputus',
                'message' => 'Device "Marketing Team" terputus dari WhatsApp. Silakan hubungkan kembali.',
                'action_url' => '/devices',
                'action_text' => 'Hubungkan Device',
            ],
        ];

        $count = 0;

        foreach ($users as $user) {
            // Skip if user already has notifications
            if ($user->notifications()->exists()) {
                continue;
            }

            foreach ($notifications as $index => $notificationData) {
                $createdAt = now()->subHours(rand(1, 72));

                $notification = new DatabaseNotification([
                    'id' => Str::uuid(),
                    'type' => 'App\\Notifications\\SystemNotification',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => $notificationData,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    // Mark first 3 as read
                    'read_at' => $index < 3 ? $createdAt->addMinutes(rand(5, 60)) : null,
                ]);

                $notification->save();
                $count++;
            }
        }

        $this->command->info("Created {$count} sample notifications for {$users->count()} admin users.");
    }
}
