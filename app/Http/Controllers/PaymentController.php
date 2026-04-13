<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\ApplicationStatusHistory;
use App\Notifications\ApplicationStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentUserId = (int) $user->id;
        $isSuperAdmin = (bool) $user?->isSuperAdmin();
        $allowedStatuses = $isSuperAdmin
            ? [
                AidApplication::STATUS_SUBMITTED,
                AidApplication::STATUS_UNDER_REVIEW,
                AidApplication::STATUS_KUIRI,
                AidApplication::STATUS_APPROVED,
                AidApplication::STATUS_DISBURSED,
                AidApplication::STATUS_REJECTED,
            ]
            : [
                AidApplication::STATUS_APPROVED,
                AidApplication::STATUS_DISBURSED,
            ];

        $status = $request->string('status')->value();
        $category = $request->string('category')->value();
        $action = $request->string('action')->value();
        $search = trim((string) $request->string('q')->value());
        $urgentFirst = $request->boolean('urgent_first');

        $applicationsQuery = AidApplication::query()
            ->with([
                'user:id,name,email',
                'paymentPreparedBy:id,name',
                'paymentApprovedBy:id,name',
            ])
            ->whereIn('status', $allowedStatuses)
            ->when(
                $status !== '' && in_array($status, $allowedStatuses, true),
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                $category !== '',
                fn ($query) => $query->whereJsonContains('category_tags', $category)
            )
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                })
            )
            ->when(
                $action === 'needs_action',
                fn ($query) => $query->whereIn('status', [
                    AidApplication::STATUS_SUBMITTED,
                    AidApplication::STATUS_UNDER_REVIEW,
                    AidApplication::STATUS_KUIRI,
                    AidApplication::STATUS_APPROVED,
                ])
            )
            ->when(
                $action === 'ready_to_disburse',
                fn ($query) => $query
                    ->where('status', AidApplication::STATUS_APPROVED)
                    ->whereNotNull('payment_prepared_at')
                    ->where('payment_prepared_by_user_id', '!=', $currentUserId)
            )
            ->when(
                $action === 'completed',
                fn ($query) => $query->where('status', AidApplication::STATUS_DISBURSED)
            );

        if ($urgentFirst) {
            $applicationsQuery
                ->orderByRaw("CASE
                    WHEN status = 'approved' AND payment_prepared_at IS NULL THEN 0
                    WHEN status = 'approved' AND payment_prepared_at IS NOT NULL AND (payment_prepared_by_user_id IS NULL OR payment_prepared_by_user_id != {$currentUserId}) THEN 1
                    WHEN status = 'under_review' THEN 2
                    WHEN status = 'submitted' THEN 3
                    WHEN status = 'kuiri' THEN 4
                    WHEN status = 'disbursed' THEN 5
                    WHEN status = 'rejected' THEN 6
                    ELSE 7
                END")
                ->orderByDesc('updated_at');
        } else {
            $applicationsQuery
                ->orderByRaw("CASE
                    WHEN status = 'approved' THEN 0
                    WHEN status = 'under_review' THEN 1
                    WHEN status = 'submitted' THEN 2
                    WHEN status = 'kuiri' THEN 3
                    WHEN status = 'disbursed' THEN 4
                    WHEN status = 'rejected' THEN 5
                    ELSE 6
                END")
                ->orderByDesc('updated_at');
        }

        $applications = $applicationsQuery
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AidApplication $application) => $this->mapPaymentApplication($application, $isSuperAdmin, $currentUserId));

        $categories = AidApplication::query()
            ->whereIn('status', $allowedStatuses)
            ->whereNotNull('category_tags')
            ->pluck('category_tags')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return Inertia::render('Payments/Index', [
            'applications' => $applications,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'category' => $category,
                'action' => $action,
                'urgent_first' => $urgentFirst,
            ],
            'categories' => $categories,
            'canManagePayments' => $isSuperAdmin,
        ]);
    }

    public function show(Request $request, AidApplication $application): Response
    {
        $user = $request->user();
        $isSuperAdmin = (bool) $user?->isSuperAdmin();

        $application->load([
            'user:id,name,email,branch',
            'paymentPreparedBy:id,name',
            'paymentApprovedBy:id,name',
            'statusHistories.changedBy:id,name',
        ]);

        if (! $isSuperAdmin) {
            if (! in_array($application->status, [AidApplication::STATUS_APPROVED, AidApplication::STATUS_DISBURSED], true)) {
                abort(403, 'Akses hanya untuk rekod bayaran.');
            }
        }

        return Inertia::render('Payments/Show', [
            'application' => $this->mapPaymentApplication($application, $isSuperAdmin, (int) $user->id),
            'canManagePayments' => $isSuperAdmin,
            'submittedForm' => $application->buildFormPreview(),
        ]);
    }

    public function prepare(Request $request, AidApplication $application): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin boleh rekod transaksi bayaran.');
        }

        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_ref' => ['required', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'payment_receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($application->status !== AidApplication::STATUS_APPROVED) {
            return back()->with('error', 'Hanya permohonan berstatus approved boleh disediakan untuk bayaran.');
        }

        if ($application->payment_receipt_path && Storage::disk('public')->exists($application->payment_receipt_path)) {
            Storage::disk('public')->delete($application->payment_receipt_path);
        }

        $receiptPath = $request->file('payment_receipt')->store('payment-receipts', 'public');

        $application->update([
            'paid_amount' => $validated['paid_amount'],
            'transaction_ref' => $validated['transaction_ref'],
            'paid_at' => $validated['paid_at'],
            'payment_receipt_path' => $receiptPath,
            'payment_prepared_by_user_id' => $request->user()->id,
            'payment_prepared_at' => now(),
            'payment_approved_by_user_id' => null,
            'payment_approved_at' => null,
        ]);

        ApplicationStatusHistory::create([
            'aid_application_id' => $application->id,
            'from_status' => $application->status,
            'to_status' => $application->status,
            'changed_by_user_id' => $request->user()->id,
            'notes' => $validated['notes']
                ?? ('[Maker] Bayaran disediakan. Ref transaksi: '.$validated['transaction_ref']),
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Data bayaran berjaya disediakan. Menunggu semakan checker.');
    }

    public function disburse(Request $request, AidApplication $application): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin boleh sahkan dan bayar.');
        }

        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_ref' => ['required', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($application->status, [AidApplication::STATUS_APPROVED, AidApplication::STATUS_DISBURSED], true)) {
            return back()->with('error', 'Permohonan ini belum layak untuk bayaran.');
        }

        if ($application->status === AidApplication::STATUS_DISBURSED) {
            return back()->with('error', 'Permohonan ini telah selesai dibayar.');
        }

        if (! $application->payment_prepared_at || ! $application->payment_prepared_by_user_id) {
            return back()->with('error', 'Sila lengkapkan langkah Maker dahulu sebelum Checker sahkan bayaran.');
        }

        if ((int) $application->payment_prepared_by_user_id === (int) $request->user()->id) {
            return back()->with('error', 'Pemisahan tugas Maker-Checker: pengguna yang sama tidak boleh meluluskan bayaran.');
        }

        $fromStatus = $application->status;

        $receiptPath = $application->payment_receipt_path;
        if ($request->hasFile('payment_receipt')) {
            if ($receiptPath && Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }

            $receiptPath = $request->file('payment_receipt')->store('payment-receipts', 'public');
        }

        $application->update([
            'status' => AidApplication::STATUS_DISBURSED,
            'decided_at' => $application->decided_at ?: now(),
            'paid_amount' => $validated['paid_amount'],
            'transaction_ref' => $validated['transaction_ref'],
            'paid_at' => $validated['paid_at'],
            'payment_receipt_path' => $receiptPath,
            'payment_approved_by_user_id' => $request->user()->id,
            'payment_approved_at' => now(),
        ]);

        if ($fromStatus !== AidApplication::STATUS_DISBURSED) {
            ApplicationStatusHistory::create([
                'aid_application_id' => $application->id,
                'from_status' => $fromStatus,
                'to_status' => AidApplication::STATUS_DISBURSED,
                'changed_by_user_id' => $request->user()->id,
                'notes' => $validated['notes']
                    ?? ('Bayaran diproses. Ref transaksi: '.$validated['transaction_ref']),
                'changed_at' => now(),
            ]);
        }

        if ($application->user) {
            $application->user->notify(new ApplicationStatusNotification(
                application: $application,
                subject: 'Makluman Bayaran Permohonan BERKAT',
                message: 'Bayaran untuk permohonan anda telah diproses.',
                details: [
                    'Jumlah Dibayar: RM '.number_format((float) $validated['paid_amount'], 2),
                    'Rujukan Transaksi: '.$validated['transaction_ref'],
                    'Tarikh Bayaran: '.Carbon::parse($validated['paid_at'])->format('d M Y H:i'),
                    'Status semasa: DISBURSED',
                ],
            ));
        }

        return back()->with('success', 'Bayaran berjaya dikemaskini.');
    }

    public function export(Request $request)
    {
        $status = $request->string('status')->value() ?: AidApplication::STATUS_DISBURSED;

        $rows = AidApplication::query()
            ->with(['user:id,name,email', 'paymentPreparedBy:id,name', 'paymentApprovedBy:id,name'])
            ->where('status', $status)
            ->orderByDesc('paid_at')
            ->orderByDesc('updated_at')
            ->get();

        $filename = 'bayaran-'.$status.'-'.now()->format('Ymd-His').'.csv';

        return ResponseFacade::streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'No Rujukan',
                'Nama Pemohon',
                'Emel Pemohon',
                'Status',
                'Jumlah Dimohon',
                'Jumlah Dibayar',
                'Rujukan Transaksi',
                'Tarikh Bayaran',
                'Maker',
                'Tarikh Maker',
                'Checker',
                'Tarikh Checker',
            ]);

            foreach ($rows as $application) {
                fputcsv($handle, [
                    $application->reference_no ?: 'APP-'.$application->id,
                    $application->user?->name,
                    $application->user?->email,
                    $application->status,
                    $application->requested_amount,
                    $application->paid_amount,
                    $application->transaction_ref,
                    optional($application->paid_at)->format('Y-m-d H:i:s'),
                    $application->paymentPreparedBy?->name,
                    optional($application->payment_prepared_at)->format('Y-m-d H:i:s'),
                    $application->paymentApprovedBy?->name,
                    optional($application->payment_approved_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function mapPaymentApplication(AidApplication $application, bool $isSuperAdmin, int $currentUserId): array
    {
        return [
            'id' => $application->id,
            'reference_no' => $application->reference_no ?: 'APP-'.$application->id,
            'applicant_name' => $application->user?->name ?: 'Tidak diketahui',
            'applicant_email' => $application->user?->email,
            'applicant_branch' => $application->user?->branch,
            'status' => $application->status,
            'category' => $this->mapCategory($application),
            'requested_amount' => $application->requested_amount,
            'paid_amount' => $application->paid_amount,
            'transaction_ref' => $application->transaction_ref,
            'payment_receipt_url' => $application->payment_receipt_path
                ? asset('storage/'.$application->payment_receipt_path)
                : null,
            'decided_at' => optional($application->decided_at)->format('d M Y H:i') ?: '-',
            'paid_at' => optional($application->paid_at)->format('d M Y H:i') ?: '-',
            'paid_at_input' => optional($application->paid_at)->format('Y-m-d\TH:i'),
            'payment_prepared_at' => optional($application->payment_prepared_at)->format('d M Y H:i') ?: '-',
            'payment_approved_at' => optional($application->payment_approved_at)->format('d M Y H:i') ?: '-',
            'payment_prepared_by' => $application->paymentPreparedBy?->name,
            'payment_approved_by' => $application->paymentApprovedBy?->name,
            'is_prepared' => (bool) $application->payment_prepared_at,
            'can_prepare' => $isSuperAdmin && $application->status === AidApplication::STATUS_APPROVED,
            'can_disburse' => $isSuperAdmin && $application->status === AidApplication::STATUS_APPROVED
                && (bool) $application->payment_prepared_at
                && $application->payment_prepared_by_user_id !== $currentUserId,
            'status_histories' => $application->relationLoaded('statusHistories')
                ? $application->statusHistories->take(8)->map(fn ($history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'notes' => $history->notes,
                    'changed_at' => optional($history->changed_at)->format('d M Y H:i') ?: '-',
                    'changed_by' => $history->changedBy?->name ?: '-',
                ])->values()->all()
                : [],
        ];
    }

    private function mapCategory(AidApplication $application): string
    {
        $firstTag = collect($application->category_tags ?: [])->first();

        if (! $firstTag) {
            return 'Umum';
        }

        return ucfirst((string) $firstTag);
    }
}
