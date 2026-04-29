<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRecordedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice,
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
        $amount = number_format((float) $this->invoice->amount, 0, ',', '.');
        $paidAt = $this->invoice->paid_at->format('d/m/Y');

        return (new MailMessage)
            ->subject('Pembayaran Berhasil Dicatat')
            ->greeting("Halo {$this->invoice->tenant->name},")
            ->line("Pembayaran Anda sebesar Rp {$amount} untuk paket {$this->invoice->plan->name} telah dicatat pada tanggal {$paidAt}.")
            ->line("Durasi langganan: {$this->invoice->duration_days} hari.")
            ->action('Lihat Detail', 'https://wa.me/6281529211963')
            ->line('Terima kasih atas pembayaran Anda!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'tenant_id' => $this->invoice->tenant_id,
            'plan_name' => $this->invoice->plan->name,
            'amount' => $this->invoice->amount,
            'paid_at' => $this->invoice->paid_at->toDateString(),
        ];
    }
}
