<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\ApplicationStatusHistory;
use App\Models\NotificationBlast;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value();
        $category = $request->string('category')->value();
        $search = trim((string) $request->string('q')->value());
        $sort = $request->string('sort')->value() ?: 'newest';
        $scope = $request->string('scope')->value() ?: 'queue';

        $applicationsQuery = AidApplication::query()
            ->with('user')
            ->when(
                $scope !== 'all' && $status === '',
                fn ($query) => $query->whereIn('status', [
                    AidApplication::STATUS_SUBMITTED,
                    AidApplication::STATUS_UNDER_REVIEW,
                    AidApplication::STATUS_KUIRI,
                ])
            )
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when(
                $category !== '',
                fn ($query) => $query->whereJsonContains('category_tags', $category)
            )
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('member_no', 'like', "%{$search}%"));
                })
            );

        match ($sort) {
            'newest' => $applicationsQuery->orderByDesc('created_at'),
            'oldest' => $applicationsQuery->orderBy('created_at'),
            'applicant_az' => $applicationsQuery->orderByRaw("LOWER(COALESCE((SELECT users.name FROM users WHERE users.id = aid_applications.user_id LIMIT 1), '')) ASC"),
            'applicant_za' => $applicationsQuery->orderByRaw("LOWER(COALESCE((SELECT users.name FROM users WHERE users.id = aid_applications.user_id LIMIT 1), '')) DESC"),
            default => $applicationsQuery->orderByDesc('created_at'),
        };

        $applications = $applicationsQuery
            ->paginate(15)
            ->withQueryString();

        // Get categories from visible applications only
        $allCategories = AidApplication::query()
            ->when(
                $scope !== 'all' && $status === '',
                fn ($query) => $query->whereIn('status', [
                    AidApplication::STATUS_SUBMITTED,
                    AidApplication::STATUS_UNDER_REVIEW,
                    AidApplication::STATUS_KUIRI,
                ])
            )
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->whereNotNull('category_tags')
            ->distinct()
            ->pluck('category_tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return Inertia::render('Approvals/Index', [
            'applications' => $applications,
            'filters' => [
                'status' => $status,
                'category' => $category,
                'q' => $search,
                'sort' => $sort,
                'scope' => $scope,
            ],
            'categories' => $allCategories,
        ]);
    }

    public function show(AidApplication $application): Response
    {
        $application->load(['user', 'walletDocuments', 'statusHistories.changedBy']);

        return Inertia::render('Approvals/Show', [
            'application' => $application,
            'submittedForm' => $application->buildFormPreview(),
            'statuses' => [
                AidApplication::STATUS_UNDER_REVIEW,
                AidApplication::STATUS_KUIRI,
                AidApplication::STATUS_APPROVED,
                AidApplication::STATUS_REJECTED,
            ],
        ]);
    }

    public function updateStatus(Request $request, AidApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:under_review,kuiri,approved,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'send_notification' => ['nullable', 'boolean'],
            'notification_subject' => ['nullable', 'string', 'max:150'],
            'notification_message' => ['nullable', 'string', 'max:2000'],
            'notification_channels' => ['nullable', 'array', 'min:1'],
            'notification_channels.*' => ['in:mail,database'],
        ]);

        $from = $application->status;
        $to = $validated['status'];

        // Non-superadmin reviewers cannot flip final decisions directly.
        if (! $request->user()?->isSuperAdmin()) {
            $isApprovedToRejected = $from === AidApplication::STATUS_APPROVED && $to === AidApplication::STATUS_REJECTED;
            $isRejectedToApproved = $from === AidApplication::STATUS_REJECTED && $to === AidApplication::STATUS_APPROVED;

            if ($isApprovedToRejected || $isRejectedToApproved) {
                return back()->with('error', 'Pegawai penyemak tidak dibenarkan menukar keputusan lulus kepada tolak atau sebaliknya. Tindakan ini hanya untuk superadmin.');
            }
        }

        $application->update([
            'status' => $to,
            'reviewed_at' => in_array($to, [AidApplication::STATUS_UNDER_REVIEW, AidApplication::STATUS_KUIRI], true)
                ? now()
                : $application->reviewed_at,
            'decided_at' => in_array($to, [AidApplication::STATUS_APPROVED, AidApplication::STATUS_DISBURSED, AidApplication::STATUS_REJECTED], true)
                ? now()
                : null,
        ]);

        ApplicationStatusHistory::create([
            'aid_application_id' => $application->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by_user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
            'changed_at' => now(),
        ]);

        $shouldSendNotification = (bool) ($validated['send_notification'] ?? true);

        if ($application->user && $shouldSendNotification) {
            $template = $this->statusEmailTemplate($application, $to, $validated['notes'] ?? null);
            $channels = $this->resolveChannels($validated['notification_channels'] ?? null);
            $subject = trim((string) ($validated['notification_subject'] ?? '')) ?: $template['subject'];
            $message = trim((string) ($validated['notification_message'] ?? '')) ?: $template['message'];

            $application->user->notify(new ApplicationStatusNotification(
                application: $application,
                subject: $subject,
                message: $message,
                details: $template['details'],
                channels: $channels,
            ));

            NotificationBlast::create([
                'sent_by_user_id' => $request->user()->id,
                'target_type' => 'single',
                'target_meta' => [
                    'notification_kind' => 'application',
                    'source_module' => 'approvals',
                    'application_id' => $application->id,
                    'reference_no' => $application->reference_no,
                    'status_to' => $to,
                ],
                'subject' => $subject,
                'message' => $message,
                'channels' => $channels,
                'recipient_count' => 1,
                'recipient_user_ids' => [(int) $application->user->id],
                'sent_at' => now(),
            ]);
        }

        return back()->with('success', 'Application status updated.');
    }

    private function statusEmailTemplate(AidApplication $application, string $status, ?string $notes = null): array
    {
        if ($status === AidApplication::STATUS_APPROVED) {
            return [
                'subject' => 'Permohonan BERKAT Anda Diluluskan',
                'message' => 'Tahniah, permohonan bantuan anda telah diluluskan dan sedang menunggu proses bayaran.',
                'details' => array_filter([
                    'Jumlah Permohonan: '.($application->requested_amount ? 'RM '.number_format((float) $application->requested_amount, 2) : '-'),
                    $notes ? 'Catatan Pegawai: '.$notes : null,
                ]),
            ];
        }

        if ($status === AidApplication::STATUS_REJECTED) {
            return [
                'subject' => 'Makluman Keputusan Permohonan BERKAT',
                'message' => 'Permohonan bantuan anda tidak dapat diluluskan pada masa ini.',
                'details' => array_filter([
                    $notes ? 'Sebab / Catatan: '.$notes : 'Sebab: Sila rujuk pentadbir untuk maklumat lanjut.',
                ]),
            ];
        }

        if ($status === AidApplication::STATUS_DISBURSED) {
            return [
                'subject' => 'Bayaran Permohonan BERKAT Telah Disalurkan',
                'message' => 'Bayaran bantuan anda telah disalurkan.',
                'details' => array_filter([
                    $application->paid_amount ? 'Jumlah Dibayar: RM '.number_format((float) $application->paid_amount, 2) : null,
                    $application->transaction_ref ? 'Rujukan Transaksi: '.$application->transaction_ref : null,
                    $application->paid_at ? 'Tarikh Bayaran: '.$application->paid_at->format('d M Y H:i') : null,
                    $notes ? 'Catatan Pegawai: '.$notes : null,
                ]),
            ];
        }

        return [
            'subject' => 'Kemaskini Status Permohonan BERKAT',
            'message' => 'Status permohonan anda telah dikemaskini.',
            'details' => array_filter([
                $notes ? 'Catatan Pegawai: '.$notes : null,
            ]),
        ];
    }

    private function resolveChannels(?array $channels): array
    {
        $resolved = collect($channels ?: ['mail', 'database'])
            ->filter(fn (string $channel) => in_array($channel, ['mail', 'database'], true))
            ->unique()
            ->values()
            ->all();

        return count($resolved) ? $resolved : ['database'];
    }
}
