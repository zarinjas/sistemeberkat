<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'branch' => $user->branch,
            ]);

        return Inertia::render('System/Index', [
            'users' => $users,
            'stats' => [
                'total' => User::query()->count(),
                'admin' => User::query()->where('role', 'admin')->count(),
                'applicant' => User::query()->where('role', 'applicant')->count(),
            ],
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

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,applicant'],
        ]);

        $user->update([
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Peranan pengguna berjaya dikemaskini.');
    }
}
