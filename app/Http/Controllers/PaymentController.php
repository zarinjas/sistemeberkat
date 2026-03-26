<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\ApplicationStatusHistory;
use App\Notifications\ApplicationStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $currentUserId = $request->user()->id;

        $applications = AidApplication::query()
            ->with([
                'user:id,name,email',
                'paymentPreparedBy:id,name',
                'paymentApprovedBy:id,name',
            ])
            ->whereIn('status', [
                AidApplication::STATUS_APPROVED,
                AidApplication::STATUS_DISBURSED,
            ])
            ->orderByDesc('decided_at')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AidApplication $application) => [
                'id' => $application->id,
                'reference_no' => $application->reference_no ?: 'APP-'.$application->id,
                'applicant_name' => $application->user?->name ?: 'Tidak diketahui',
                'applicant_email' => $application->user?->email,
                'status' => $application->status,
                'requested_amount' => $application->requested_amount,
                'paid_amount' => $application->paid_amount,
                'transaction_ref' => $application->transaction_ref,
                'decided_at' => optional($application->decided_at)->format('d M Y H:i') ?: '-',
                'paid_at' => optional($application->paid_at)->format('d M Y H:i') ?: '-',
                'paid_at_input' => optional($application->paid_at)->format('Y-m-d\TH:i'),
                'payment_prepared_at' => optional($application->payment_prepared_at)->format('d M Y H:i') ?: '-',
                'payment_approved_at' => optional($application->payment_approved_at)->format('d M Y H:i') ?: '-',
                'payment_prepared_by' => $application->paymentPreparedBy?->name,
                'payment_approved_by' => $application->paymentApprovedBy?->name,
                'is_prepared' => (bool) $application->payment_prepared_at,
                'can_prepare' => $application->status === AidApplication::STATUS_APPROVED,
                'can_disburse' => $application->status === AidApplication::STATUS_APPROVED
                    && (bool) $application->payment_prepared_at
                    && $application->payment_prepared_by_user_id !== $currentUserId,
            ]);

        return Inertia::render('Payments/Index', [
            'applications' => $applications,
        ]);
    }

    public function prepare(Request $request, AidApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_ref' => ['required', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($application->status !== AidApplication::STATUS_APPROVED) {
            return back()->with('error', 'Hanya permohonan berstatus approved boleh disediakan untuk bayaran.');
        }

        $application->update([
            'paid_amount' => $validated['paid_amount'],
            'transaction_ref' => $validated['transaction_ref'],
            'paid_at' => $validated['paid_at'],
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
        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_ref' => ['required', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
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

        $application->update([
            'status' => AidApplication::STATUS_DISBURSED,
            'decided_at' => $application->decided_at ?: now(),
            'paid_amount' => $validated['paid_amount'],
            'transaction_ref' => $validated['transaction_ref'],
            'paid_at' => $validated['paid_at'],
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
}
