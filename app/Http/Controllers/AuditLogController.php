<?php

namespace App\Http\Controllers;

use App\Models\RoleChangeAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use League\Csv\Writer;

class AuditLogController extends Controller
{
    /**
     * Display role change audit history
     */
    public function index(Request $request): InertiaResponse
    {
        $query = RoleChangeAudit::query()
            ->with(['user:id,name,email,role', 'changedBy:id,name,email']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        // Filter by changed_by (who made the change)
        if ($request->filled('changed_by_id')) {
            $query->where('changed_by_user_id', $request->integer('changed_by_id'));
        }

        // Filter by old role
        if ($request->filled('old_role')) {
            $query->where('old_role', $request->string('old_role'));
        }

        // Filter by new role
        if ($request->filled('new_role')) {
            $query->where('new_role', $request->string('new_role'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('changed_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('changed_at', '<=', $request->string('date_to').' 23:59:59');
        }

        // Search by user name or email
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($subQuery) => $subQuery
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search))
                ->orWhereHas('changedBy', fn ($subQuery) => $subQuery
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            });
        }

        $audits = $query
            ->orderByDesc('changed_at')
            ->paginate(20)
            ->withQueryString();

        // Get unique users for filter dropdown
        $users = User::query()
            ->whereHas('roleChangeAudits')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // Get superadmins for filter dropdown
        $superadmins = User::query()
            ->where('role', 'superadmin')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Audit/RoleChanges', [
            'audits' => $audits,
            'filters' => [
                'user_id' => $request->integer('user_id'),
                'changed_by_id' => $request->integer('changed_by_id'),
                'old_role' => $request->string('old_role'),
                'new_role' => $request->string('new_role'),
                'date_from' => $request->string('date_from'),
                'date_to' => $request->string('date_to'),
                'search' => $request->string('search'),
            ],
            'filterOptions' => [
                'users' => $users,
                'superadmins' => $superadmins,
                'roles' => ['applicant', 'admin', 'superadmin'],
            ],
            'stats' => [
                'total_changes' => RoleChangeAudit::count(),
                'today_changes' => RoleChangeAudit::whereDate('changed_at', today())->count(),
                'promoted_to_admin' => RoleChangeAudit::where('new_role', 'admin')->count(),
                'demoted_from_admin' => RoleChangeAudit::where('old_role', 'admin')->count(),
            ],
        ]);
    }

    /**
     * Export role change audits to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = $this->applyFilters(RoleChangeAudit::query(), $request);

        $audits = $query
            ->with(['user:id,name,email', 'changedBy:id,name,email'])
            ->orderByDesc('changed_at')
            ->get();

        // Prepare data for view
        $tableRows = $audits->map(fn ($audit) => [
            'date_changed' => $audit->changed_at->format('Y-m-d H:i:s'),
            'user_name' => $audit->user?->name ?? '-',
            'user_email' => $audit->user?->email ?? '-',
            'old_role' => $audit->old_role,
            'new_role' => $audit->new_role,
            'changed_by_name' => $audit->changedBy?->name ?? '-',
            'changed_by_email' => $audit->changedBy?->email ?? '-',
        ])->toArray();

        $html = view('exports.audit-pdf', [
            'audits' => $tableRows,
            'generated_at' => now()->format('d M Y H:i:s'),
            'total_records' => count($tableRows),
        ])->render();

        $pdf = \PDF::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('isPhpEnabled', true)
            ->setOption('dpi', 96);

        return $pdf->download('role-change-audit-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * Export role change audits to CSV
     */
    public function exportCsv(Request $request): Response
    {
        $query = $this->applyFilters(RoleChangeAudit::query(), $request);

        $audits = $query
            ->with(['user:id,name,email', 'changedBy:id,name,email'])
            ->orderByDesc('changed_at')
            ->get();

        // Create CSV writer
        $csv = Writer::createFromString('');
        $csv->insertOne([
            'Date Changed',
            'User Name',
            'User Email',
            'Old Role',
            'New Role',
            'Changed By (Name)',
            'Changed By (Email)',
            'Reason',
        ]);

        foreach ($audits as $audit) {
            $csv->insertOne([
                $audit->changed_at->format('Y-m-d H:i:s'),
                $audit->user?->name ?? '-',
                $audit->user?->email ?? '-',
                $audit->old_role,
                $audit->new_role,
                $audit->changedBy?->name ?? '-',
                $audit->changedBy?->email ?? '-',
                $audit->reason ?? '-',
            ]);
        }

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="role-change-audit-'.now()->format('Y-m-d-His').'.csv"',
        ]);
    }

    /**
     * Get audit statistics
     */
    public function statistics(Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? $request->string('date_from')
            : now()->subDays(30)->toDateString();

        $dateTo = $request->filled('date_to')
            ? $request->string('date_to')
            : now()->toDateString();

        $query = RoleChangeAudit::whereBetween('changed_at', [$dateFrom, $dateTo]);

        return [
            'total_changes' => $query->count(),
            'by_role' => [
                'promoted_to_admin' => (clone $query)->where('new_role', 'admin')->count(),
                'promoted_to_superadmin' => (clone $query)->where('new_role', 'superadmin')->count(),
                'demoted_from_admin' => (clone $query)->where('old_role', 'admin')->count(),
                'demoted_from_superadmin' => (clone $query)->where('old_role', 'superadmin')->count(),
            ],
            'by_superadmin' => (clone $query)
                ->with('changedBy:id,name')
                ->get()
                ->groupBy('changedBy.name')
                ->mapWithKeys(fn ($items, $name) => [$name => $items->count()])
                ->toArray(),
        ];
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('changed_by_id')) {
            $query->where('changed_by_user_id', $request->integer('changed_by_id'));
        }

        if ($request->filled('old_role')) {
            $query->where('old_role', $request->string('old_role'));
        }

        if ($request->filled('new_role')) {
            $query->where('new_role', $request->string('new_role'));
        }

        if ($request->filled('date_from')) {
            $query->where('changed_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('changed_at', '<=', $request->string('date_to').' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($subQuery) => $subQuery
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search))
                ->orWhereHas('changedBy', fn ($subQuery) => $subQuery
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            });
        }

        return $query;
    }
}
