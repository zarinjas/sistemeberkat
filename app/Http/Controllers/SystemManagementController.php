<?php

namespace App\Http\Controllers;

use App\Models\MemberOperationAudit;
use App\Models\User;
use App\Models\RoleChangeAudit;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SystemManagementController extends Controller
{
    private const MEMBERSHIP_IMPORT_COLUMNS = [
        'nric',
        'name',
        'email',
        'phone',
        'member_no',
        'job_title',
        'department',
        'state',
    ];

    public function index(Request $request): Response
    {
        $this->ensureAdminOrSuperAdmin($request);

        $search = trim((string) $request->string('q'));
        $role = (string) $request->string('role');
        $activation = (string) $request->string('activation');
        $perPage = (int) $request->integer('per_page', 20);

        if (! in_array($role, ['', 'admin', 'applicant'], true)) {
            $role = '';
        }

        if (! in_array($activation, ['', 'activated', 'pending'], true)) {
            $activation = '';
        }

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nric', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->when($activation === 'activated', fn ($query) => $query->where('first_login_completed', true))
            ->when($activation === 'pending', fn ($query) => $query->where('first_login_completed', false))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nric' => $user->nric,
                'member_no' => $user->member_no,
                'role' => $user->role,
                'first_login_completed' => (bool) $user->first_login_completed,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'department' => $user->department,
                'state' => $user->state,
                'branch' => $user->branch,
                'address' => $user->address,
            ]);

        return Inertia::render('System/Index', [
            'users' => $users,
            'stats' => [
                'total' => User::query()->count(),
                'admin' => User::query()->where('role', 'admin')->count(),
                'applicant' => User::query()->where('role', 'applicant')->count(),
            ],
            'canManageMembers' => (bool) $request->user()?->isSuperAdmin(),
            'canImportExportMembers' => (bool) $request->user()?->isSuperAdmin(),
            'membershipImport' => [
                'sampleColumns' => self::MEMBERSHIP_IMPORT_COLUMNS,
                'summary' => session('import_summary'),
            ],
            'filters' => [
                'q' => $search,
                'role' => $role,
                'activation' => $activation,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->ensureAdminOrSuperAdmin($request);

        if (! $user->isApplicant()) {
            abort(404, 'Rekod ahli tidak dijumpai.');
        }

        return Inertia::render('System/ShowMember', [
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nric' => $user->nric,
                'member_no' => $user->member_no,
                'role' => $user->role,
                'job_title' => $user->job_title,
                'department' => $user->department,
                'state' => $user->state,
                'branch' => $user->branch,
                'address' => $user->address,
                'first_login_completed' => (bool) $user->first_login_completed,
                'profile_photo_url' => $user->profile_photo_path
                    ? asset('storage/'.$user->profile_photo_path)
                    : null,
            ],
            'canEditMember' => (bool) $request->user()?->isSuperAdmin(),
            'canViewMemberCard' => (bool) $request->user()?->isSuperAdmin(),
        ]);
    }

    public function storeMember(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'nric' => ['required', 'string', 'max:20', 'unique:users,nric'],
            'phone' => ['nullable', 'string', 'max:30'],
            'member_no' => ['nullable', 'string', 'max:50', 'unique:users,member_no'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'first_login_completed' => ['nullable', 'boolean'],
        ]);

        $cleanNric = preg_replace('/\D+/', '', (string) $validated['nric']);
        if ($cleanNric === '') {
            return back()->with('error', 'No. IC ahli tidak sah.');
        }

        $email = trim((string) ($validated['email'] ?? ''));

        $member = User::query()->create([
            'name' => $validated['name'],
            'email' => $email !== '' ? strtolower($email) : "nric{$cleanNric}@pending.local",
            'password' => Hash::make(Str::random(24)),
            'role' => 'applicant',
            'nric' => $cleanNric,
            'phone' => $validated['phone'] ?? null,
            'member_no' => $validated['member_no'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'department' => $validated['department'] ?? null,
            'state' => $validated['state'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'address' => $validated['address'] ?? null,
            'first_login_completed' => (bool) ($validated['first_login_completed'] ?? false),
        ]);

        $this->logMemberOperation(
            actorUserId: (int) $request->user()->id,
            action: 'member_manual_create',
            memberUserId: (int) $member->id,
            context: [
                'member_no' => $member->member_no,
                'branch' => $member->branch,
                'first_login_completed' => (bool) $member->first_login_completed,
            ],
        );

        return redirect()
            ->route('admin.system.members.show', $member)
            ->with('success', 'Ahli berjaya ditambah secara manual.');
    }

    public function updateMember(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        if (! $user->isApplicant()) {
            return back()->with('error', 'Hanya rekod ahli boleh dikemaskini dari modul ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'nric' => ['required', 'string', 'max:20', 'unique:users,nric,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'member_no' => ['nullable', 'string', 'max:50', 'unique:users,member_no,'.$user->id],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'first_login_completed' => ['nullable', 'boolean'],
        ]);

        $cleanNric = preg_replace('/\D+/', '', (string) $validated['nric']);
        if ($cleanNric === '') {
            return back()->with('error', 'No. IC ahli tidak sah.');
        }

        $email = trim((string) ($validated['email'] ?? ''));
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'member_no' => $user->member_no,
            'job_title' => $user->job_title,
            'department' => $user->department,
            'state' => $user->state,
            'branch' => $user->branch,
            'address' => $user->address,
            'first_login_completed' => (bool) $user->first_login_completed,
        ];

        $user->update([
            'name' => $validated['name'],
            'email' => $email !== '' ? strtolower($email) : "nric{$cleanNric}@pending.local",
            'nric' => $cleanNric,
            'phone' => $validated['phone'] ?? null,
            'member_no' => $validated['member_no'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'department' => $validated['department'] ?? null,
            'state' => $validated['state'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'address' => $validated['address'] ?? null,
            'first_login_completed' => (bool) ($validated['first_login_completed'] ?? false),
        ]);

        $after = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'member_no' => $user->member_no,
            'job_title' => $user->job_title,
            'department' => $user->department,
            'state' => $user->state,
            'branch' => $user->branch,
            'address' => $user->address,
            'first_login_completed' => (bool) $user->first_login_completed,
        ];

        $changes = [];
        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) !== $value) {
                $changes[$key] = [
                    'from' => $before[$key] ?? null,
                    'to' => $value,
                ];
            }
        }

        if (! empty($changes)) {
            $this->logMemberOperation(
                actorUserId: (int) $request->user()->id,
                action: 'member_manual_update',
                memberUserId: (int) $user->id,
                context: [
                    'changed_fields' => array_keys($changes),
                    'changes' => $changes,
                ],
            );
        }

        return back()->with('success', 'Maklumat ahli berjaya dikemaskini.');
    }

    public function exportMembersCsv(Request $request): HttpResponse
    {
        $this->ensureSuperAdmin($request);

        $filename = 'ahli-berkat-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $search = trim((string) $request->string('q'));
        $query = User::query()
            ->where('role', 'applicant')
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nric', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $exportCount = (clone $query)->count();
        $this->logMemberOperation(
            actorUserId: (int) $request->user()->id,
            action: 'member_csv_export',
            context: [
                'filters' => [
                    'q' => $search,
                ],
                'record_count' => $exportCount,
            ],
        );

        $callback = static function () use ($search): void {
            $output = fopen('php://output', 'w');

            if (! $output) {
                return;
            }

            fputcsv($output, ['member_no', 'name', 'email', 'nric', 'phone', 'job_title', 'department', 'state', 'branch', 'activation']);

            User::query()
                ->where('role', 'applicant')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('nric', 'like', "%{$search}%")
                            ->orWhere('member_no', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->chunk(500, function ($users) use ($output) {
                    foreach ($users as $user) {
                        fputcsv($output, [
                            $user->member_no,
                            $user->name,
                            $user->email,
                            $user->nric,
                            $user->phone,
                            $user->job_title,
                            $user->department,
                            $user->state,
                            $user->branch,
                            $user->first_login_completed ? 'aktif' : 'belum_aktif',
                        ]);
                    }
                });

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'role' => ['required', 'in:admin,applicant'],
        ]);

        $oldRole = $user->role;
        $newRole = $validated['role'];

        // Only log if role actually changed
        if ($oldRole !== $newRole) {
            $user->update([
                'role' => $newRole,
            ]);

            // Create audit log
            RoleChangeAudit::create([
                'user_id' => $user->id,
                'changed_by_user_id' => $request->user()->id,
                'old_role' => $oldRole,
                'new_role' => $newRole,
                'reason' => null,
                'changed_at' => now(),
            ]);

            return back()->with('success', 'Peranan pengguna berjaya dikemaskini.');
        }

        return back()->with('info', 'Tiada perubahan peranan pengguna.');
    }

    private function ensureAdminOrSuperAdmin(Request $request): void
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->isSuperAdmin())) {
            abort(403);
        }
    }

    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin dibenarkan untuk tindakan ini.');
        }
    }

    private function logMemberOperation(int $actorUserId, string $action, ?int $memberUserId = null, array $context = []): void
    {
        MemberOperationAudit::create([
            'actor_user_id' => $actorUserId,
            'member_user_id' => $memberUserId,
            'action' => $action,
            'context' => $context,
        ]);
    }
}
