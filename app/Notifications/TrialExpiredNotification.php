<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Tenant $tenant,
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
            ->subject('Masa Trial Anda Telah Berakhir')
            ->greeting("Halo {$this->tenant->name},")
            ->line("Masa trial Anda telah berakhir pada tanggal {$expiryDate}.")
            ->line('Akun Anda telah ditangguhkan. Untuk mengaktifkan kembali layanan, silakan upgrade ke paket berbayar.')
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
            'trial_ended_at' => $this->tenant->trial_ends_at->toDateTimeString(),
        ];
    }
}
