<?php

namespace App\Notifications;

use App\Models\AidApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification
{
    public function __construct(
        private readonly AidApplication $application,
        private readonly string $subject,
        private readonly string $message,
        private readonly array $details = [],
        private readonly array $channels = ['mail', 'database'],
        private readonly ?string $imageUrl = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return collect($this->channels)
            ->filter(fn (string $channel) => in_array($channel, ['mail', 'database'], true))
            ->when(! $notifiable?->email, fn ($channels) => $channels->reject(fn ($channel) => $channel === 'mail'))
            ->values()
            ->all();
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

        if ($this->imageUrl) {
            $mailMessage->line('Lampiran imej: '.$this->imageUrl);
        }

        return $mailMessage
            ->action('Lihat Dashboard', url('/dashboard'))
            ->line('Terima kasih kerana menggunakan sistem BERKAT.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
            'reference_no' => $this->application->reference_no ?: 'APP-'.$this->application->id,
            'status' => (string) $this->application->status,
            'details' => $this->details,
            'image_url' => $this->imageUrl,
            'dashboard_url' => url('/dashboard'),
        ];
    }
}
