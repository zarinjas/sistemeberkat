<?php

namespace App\Notifications;

use App\Models\AidApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AidApplication $application,
        private readonly string $subject,
        private readonly string $message,
        private readonly array $details = [],
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage())
            ->subject($this->subject)
            ->greeting('Assalamualaikum / Salam Sejahtera, '.$notifiable->name)
            ->line($this->message)
            ->line('No. Rujukan Permohonan: '.($this->application->reference_no ?: 'APP-'.$this->application->id))
            ->line('Status Terkini: '.strtoupper((string) $this->application->status));

        foreach ($this->details as $detail) {
            $mailMessage->line((string) $detail);
        }

        return $mailMessage
            ->action('Lihat Dashboard', url('/dashboard'))
            ->line('Terima kasih kerana menggunakan sistem BERKAT.');
    }
}
