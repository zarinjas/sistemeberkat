<?php

namespace App\Http\Controllers;

use App\Models\ApplicationStatusHistory;
use App\Models\LoginAccessLog;
use App\Models\MemberOperationAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperationAuditController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin boleh akses Audit Operasi.');
        }

        $keyword = $request->string('q')->value();
        $memberAction = trim((string) $request->string('member_action')->value());
        $loginKeyword = trim((string) $request->string('login_q')->value());

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

        $memberLogs = MemberOperationAudit::query()
            ->with([
                'actor:id,name,email',
                'member:id,name,email,member_no',
            ])
            ->when($memberAction !== '', fn ($query) => $query->where('action', $memberAction))
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('action', 'like', "%{$keyword}%")
                        ->orWhereHas('actor', fn ($user) => $user->where('name', 'like', "%{$keyword}%"))
                        ->orWhereHas('member', fn ($member) => $member
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('member_no', 'like', "%{$keyword}%"));
                });
            })
            ->latest()
            ->paginate(25, ['*'], 'member_page')
            ->withQueryString()
            ->through(fn (MemberOperationAudit $audit) => [
                'id' => $audit->id,
                'action' => $audit->action,
                'actor_name' => $audit->actor?->name ?: 'Sistem',
                'member_name' => $audit->member?->name ?: '-',
                'member_no' => $audit->member?->member_no ?: '-',
                'context' => $audit->context ?: [],
                'created_at' => optional($audit->created_at)->format('d M Y H:i:s') ?: '-',
            ]);

        $loginLogs = LoginAccessLog::query()
            ->when($loginKeyword !== '', function ($query) use ($loginKeyword) {
                $query->where(function ($inner) use ($loginKeyword) {
                    $inner->where('user_name', 'like', "%{$loginKeyword}%")
                        ->orWhere('user_email', 'like', "%{$loginKeyword}%")
                        ->orWhere('ip_address', 'like', "%{$loginKeyword}%")
                        ->orWhere('location_summary', 'like', "%{$loginKeyword}%")
                        ->orWhere('isp', 'like', "%{$loginKeyword}%");
                });
            })
            ->latest('logged_in_at')
            ->paginate(25, ['*'], 'login_page')
            ->withQueryString()
            ->through(fn (LoginAccessLog $log) => [
                'id' => $log->id,
                'user_name' => $log->user_name ?: '-',
                'user_email' => $log->user_email ?: '-',
                'user_role' => $log->user_role ?: '-',
                'login_type' => $log->login_type ?: 'standard',
                'ip_address' => $log->ip_address ?: '-',
                'location_summary' => $log->location_summary ?: '-',
                'isp' => $log->isp ?: '-',
                'user_agent' => $log->user_agent ?: '-',
                'logged_in_at' => optional($log->logged_in_at)->format('d M Y H:i:s') ?: '-',
            ]);

        return Inertia::render('System/Audit', [
            'logs' => $logs,
            'memberLogs' => $memberLogs,
            'loginLogs' => $loginLogs,
            'filters' => [
                'q' => $keyword,
                'member_action' => $memberAction,
                'login_q' => $loginKeyword,
            ],
        ]);
    }
}
