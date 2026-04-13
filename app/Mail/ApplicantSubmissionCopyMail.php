<?php

namespace App\Mail;

use App\Models\AidApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ApplicantSubmissionCopyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AidApplication $application,
        public string $formTitle,
        public string $submittedAtLabel,
        public string $amountLabel,
        public string $applicationUrl,
    ) {
    }

    public function build(): self
    {
        $mail = $this
            ->subject('Salinan Permohonan Anda: '.($this->application->reference_no ?: 'APP-'.$this->application->id))
            ->view('emails.applications.submitted-applicant-copy');

        if ($this->application->submission_pdf_path && Storage::disk('local')->exists($this->application->submission_pdf_path)) {
            $mail->attachFromStorageDisk(
                'local',
                $this->application->submission_pdf_path,
                'borang-'.$this->application->reference_no.'.pdf'
            );
        }

        return $mail;
    }
}