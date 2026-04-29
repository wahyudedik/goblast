<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * Accepts either an Alert model (for single alert notifications)
     * or an integer count (for batch alert notifications from CheckAlerts command).
     */
    public function __construct(
        public Alert|int $alert,
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
        if ($this->alert instanceof Alert) {
            return $this->buildAlertMail();
        }

        return $this->buildBatchMail();
    }

    /**
     * Build mail for a single Alert model notification.
     */
    protected function buildAlertMail(): MailMessage
    {
        /** @var Alert $alert */
        $alert = $this->alert;

        $severityLabel = mb_strtoupper($alert->severity);

        return (new MailMessage)
            ->subject("System Alert [{$severityLabel}]: {$alert->type}")
            ->greeting('Hello Superadmin,')
            ->line("A new **{$alert->severity}** alert has been created:")
            ->line("**Type:** {$alert->type}")
            ->line("**Message:** {$alert->message}")
            ->action('View Alert', url("/admin/alerts/{$alert->id}"))
            ->line('Please review and resolve this alert in the admin dashboard.');
    }

    /**
     * Build mail for a batch alert count notification.
     */
    protected function buildBatchMail(): MailMessage
    {
        $count = $this->alert;

        return (new MailMessage)
            ->subject("System Alert: {$count} New Alert(s) Detected")
            ->greeting('Hello Superadmin,')
            ->line("The system has detected {$count} new alert(s) that require your attention.")
            ->line('Please check the admin dashboard to review and resolve these alerts.')
            ->action('View Alerts', url('/admin/alerts'))
            ->line('This is an automated notification from the WA Automation system.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if ($this->alert instanceof Alert) {
            return [
                'alert_id' => $this->alert->id,
                'type' => $this->alert->type,
                'severity' => $this->alert->severity,
                'message' => $this->alert->message,
                'timestamp' => now()->toDateTimeString(),
            ];
        }

        return [
            'alerts_created' => $this->alert,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
