<?php

namespace App\Services;

use App\Mail\AdminApplicationSubmittedMail;
use App\Mail\ApplicantSubmissionCopyMail;
use App\Models\AidApplication;
use App\Models\NotificationBlast;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ApplicationSubmissionNotificationService
{
    public function __construct(
        private readonly ApplicationPdfService $applicationPdfService,
    ) {
    }

    public function sendSubmissionEmails(AidApplication $application): void
    {
        $application->loadMissing('user');
        $this->ensureSubmissionPdf($application);

        $formPreview = $application->buildFormPreview();
        $formTitle = (string) ($formPreview['title'] ?? 'Borang Permohonan');
        $submittedAtLabel = optional($application->submitted_at ?? $application->created_at)?->format('d M Y, h:i A') ?: '-';
        $amountLabel = $application->requested_amount
            ? 'RM '.number_format((float) $application->requested_amount, 2)
            : 'Tidak dinyatakan';

        $adminRecipients = User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->unique(fn (User $user) => strtolower(trim((string) $user->email)));

        foreach ($adminRecipients as $adminRecipient) {
            try {
                Mail::to($adminRecipient->email)->send(new AdminApplicationSubmittedMail(
                    application: $application,
                    formTitle: $formTitle,
                    submittedAtLabel: $submittedAtLabel,
                    amountLabel: $amountLabel,
                    reviewUrl: url('/admin/approvals'),
                ));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($adminRecipients->isNotEmpty()) {
            NotificationBlast::create([
                'sent_by_user_id' => null,
                'target_type' => 'group',
                'target_meta' => [
                    'notification_kind' => 'application',
                    'source_module' => 'applications',
                    'event' => 'application_submitted_admin_alert',
                    'application_id' => $application->id,
                    'reference_no' => $application->reference_no,
                    'form_title' => $formTitle,
                    'audience' => 'admin',
                    'triggered_by_user_id' => (int) $application->user_id,
                ],
                'subject' => 'Permohonan Baru Dihantar: '.($application->reference_no ?: 'APP-'.$application->id),
                'message' => 'Notifikasi sistem kepada admin bagi pemakluman permohonan baharu yang telah dihantar.',
                'channels' => ['mail'],
                'recipient_count' => $adminRecipients->count(),
                'recipient_user_ids' => $adminRecipients->map(fn (User $user) => (int) $user->id)->values()->all(),
                'sent_at' => now(),
            ]);
        }

        if ($application->user?->email) {
            try {
                Mail::to($application->user->email)->send(new ApplicantSubmissionCopyMail(
                    application: $application,
                    formTitle: $formTitle,
                    submittedAtLabel: $submittedAtLabel,
                    amountLabel: $amountLabel,
                    applicationUrl: url('/applications'),
                ));
            } catch (\Throwable $exception) {
                report($exception);
            }

            NotificationBlast::create([
                'sent_by_user_id' => null,
                'target_type' => 'single',
                'target_meta' => [
                    'notification_kind' => 'application',
                    'source_module' => 'applications',
                    'event' => 'application_submitted_applicant_copy',
                    'application_id' => $application->id,
                    'reference_no' => $application->reference_no,
                    'form_title' => $formTitle,
                    'audience' => 'applicant',
                    'triggered_by_user_id' => (int) $application->user_id,
                ],
                'subject' => 'Salinan Permohonan Anda: '.($application->reference_no ?: 'APP-'.$application->id),
                'message' => 'Salinan rasmi permohonan telah dihantar kepada pemohon melalui email berdaftar.',
                'channels' => ['mail'],
                'recipient_count' => 1,
                'recipient_user_ids' => [(int) $application->user_id],
                'sent_at' => now(),
            ]);
        }
    }

    private function ensureSubmissionPdf(AidApplication $application): void
    {
        $pdfPath = $application->submission_pdf_path;

        if (! $pdfPath || ! Storage::disk('local')->exists($pdfPath)) {
            $pdfPath = $this->applicationPdfService->generateAndStore($application);

            $application->update([
                'submission_pdf_path' => $pdfPath,
                'submission_pdf_generated_at' => now(),
            ]);

            $application->refresh();
        }
    }
}