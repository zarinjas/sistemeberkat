<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\ApplicationStatusHistory;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $applications = AidApplication::query()
            ->with('user')
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('status', $status))
            ->when(
                $request->string('category')->value(),
                fn ($query, $category) => $query->whereJsonContains('category_tags', $category)
            )
            ->orderByDesc('priority_score')
            ->orderByDesc('submitted_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $allCategories = AidApplication::query()
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
                'status' => $request->string('status')->value(),
                'category' => $request->string('category')->value(),
            ],
            'categories' => $allCategories,
        ]);
    }

    public function show(AidApplication $application): Response
    {
        $application->load(['user', 'walletDocuments', 'statusHistories.changedBy']);

        return Inertia::render('Approvals/Show', [
            'application' => $application,
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
        ]);

        $from = $application->status;
        $to = $validated['status'];

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

        if ($application->user) {
            $template = $this->statusEmailTemplate($application, $to, $validated['notes'] ?? null);
            $application->user->notify(new ApplicationStatusNotification(
                application: $application,
                subject: $template['subject'],
                message: $template['message'],
                details: $template['details'],
            ));
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
}
