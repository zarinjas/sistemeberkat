<?php

namespace App\Http\Controllers;

use App\Models\ApplicationStatusHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperationAuditController extends Controller
{
    public function index(Request $request): Response
    {
        $keyword = $request->string('q')->value();

        $logs = ApplicationStatusHistory::query()
            ->with([
                'application:id,reference_no,user_id',
                'application.user:id,name,email',
                'changedBy:id,name,email',
            ])
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('notes', 'like', "%{$keyword}%")
                        ->orWhere('from_status', 'like', "%{$keyword}%")
                        ->orWhere('to_status', 'like', "%{$keyword}%")
                        ->orWhereHas('application', fn ($application) => $application->where('reference_no', 'like', "%{$keyword}%"))
                        ->orWhereHas('changedBy', fn ($user) => $user->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->orderByDesc('changed_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ApplicationStatusHistory $history) => [
                'id' => $history->id,
                'reference_no' => $history->application?->reference_no ?: 'APP-'.($history->aid_application_id ?? '-'),
                'applicant_name' => $history->application?->user?->name ?: '-',
                'from_status' => $history->from_status ?: '-',
                'to_status' => $history->to_status,
                'changed_by' => $history->changedBy?->name ?: 'Sistem',
                'notes' => $history->notes ?: '-',
                'changed_at' => optional($history->changed_at)->format('d M Y H:i:s') ?: '-',
            ]);

        return Inertia::render('System/Audit', [
            'logs' => $logs,
            'filters' => [
                'q' => $keyword,
            ],
        ]);
    }
}
