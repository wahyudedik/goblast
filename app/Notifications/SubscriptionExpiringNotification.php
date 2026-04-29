<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public int $daysRemaining,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $expiryDate = $this->subscription->ends_at->format('d/m/Y');

        return (new MailMessage)
            ->subject("Langganan Anda Akan Berakhir dalam {$this->daysRemaining} Hari")
            ->greeting("Halo {$this->subscription->tenant->name},")
            ->line("Langganan Anda untuk paket {$this->subscription->plan->name} akan berakhir dalam {$this->daysRemaining} hari pada tanggal {$expiryDate}.")
            ->line('Untuk melanjutkan layanan tanpa gangguan, silakan perpanjang langganan Anda.')
            ->action('Hubungi Kami', 'https://wa.me/6281529211963')
            ->line('Terima kasih telah menggunakan layanan kami!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan->name,
            'days_remaining' => $this->daysRemaining,
            'expires_at' => $this->subscription->ends_at->toDateTimeString(),
        ];
    }
}
