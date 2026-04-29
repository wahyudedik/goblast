<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Tenant $tenant,
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
        $expiryDate = $this->tenant->trial_ends_at->format('d/m/Y');

        return (new MailMessage)
            ->subject("Masa Trial Anda Akan Berakhir dalam {$this->daysRemaining} Hari")
            ->greeting("Halo {$this->tenant->name},")
            ->line("Masa trial Anda akan berakhir dalam {$this->daysRemaining} hari pada tanggal {$expiryDate}.")
            ->line('Untuk melanjutkan menggunakan layanan kami, silakan upgrade ke paket berbayar.')
            ->action('Hubungi Kami', 'https://wa.me/6281529211963')
            ->line('Terima kasih telah mencoba layanan kami!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'days_remaining' => $this->daysRemaining,
            'trial_ends_at' => $this->tenant->trial_ends_at->toDateTimeString(),
        ];
    }
}
