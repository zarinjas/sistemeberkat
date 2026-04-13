<?php

namespace App\Mail;

use App\Models\AidApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AidApplication $application,
        public string $formTitle,
        public string $submittedAtLabel,
        public string $amountLabel,
        public string $reviewUrl,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Permohonan Baru Dihantar: '.($this->application->reference_no ?: 'APP-'.$this->application->id))
            ->view('emails.applications.submitted-admin');
    }
}