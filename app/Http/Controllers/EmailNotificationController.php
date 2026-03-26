<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EmailNotificationController extends Controller
{
    public function index(): Response
    {
        $applications = AidApplication::query()
            ->with('user:id,name,email,branch')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(fn (AidApplication $application) => [
                'id' => $application->id,
                'reference_no' => $application->reference_no ?: 'APP-'.$application->id,
                'applicant_name' => $application->user?->name ?: 'Tidak diketahui',
                'applicant_branch' => $application->user?->branch,
                'applicant_email' => $application->user?->email,
                'status' => $application->status,
                'updated_at' => optional($application->updated_at)->format('d M Y H:i') ?: '-',
            ])
            ->values();

        $statusGroups = AidApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($item) => [
                'value' => $item->status,
                'label' => strtoupper((string) $item->status),
                'count' => (int) $item->total,
            ])
            ->values();

        $branchGroups = AidApplication::query()
            ->whereHas('user', fn ($query) => $query->whereNotNull('branch')->where('branch', '!=', ''))
            ->with('user:id,branch')
            ->get()
            ->pluck('user.branch')
            ->filter()
            ->countBy()
            ->map(fn ($count, $branch) => [
                'value' => (string) $branch,
                'label' => (string) $branch,
                'count' => (int) $count,
            ])
            ->values();

        return Inertia::render('Notifications/Index', [
            'applications' => $applications,
            'groups' => [
                'status' => $statusGroups,
                'branch' => $branchGroups,
            ],
        ]);
    }

    public function send(Request $request, AidApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if (! $application->user?->email) {
            return back()->with('error', 'Emel pemohon tidak dijumpai.');
        }

        $application->user->notify(new ApplicationStatusNotification(
            application: $application,
            subject: $validated['subject'],
            message: $validated['message'],
        ));

        return back()->with('success', 'Notifikasi emel berjaya dihantar.');
    }

    public function sendBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->targetValidationRules(),
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $targets = $this->resolveTargets($validated);

        if ($targets->isEmpty()) {
            return back()->with('error', 'Tiada penerima emel yang sah untuk sasaran dipilih.');
        }

        foreach ($targets as $application) {
            $application->user->notify(new ApplicationStatusNotification(
                application: $application,
                subject: $validated['subject'],
                message: $validated['message'],
            ));
        }

        return back()->with('success', 'Notifikasi emel berjaya dihantar kepada '.$targets->count().' penerima.');
    }

    public function previewCount(Request $request): JsonResponse
    {
        $validated = $request->validate($this->targetValidationRules());

        $targets = $this->resolveTargets($validated);

        return response()->json([
            'count' => $targets->count(),
        ]);
    }

    private function targetValidationRules(): array
    {
        return [
            'target_type' => ['required', 'in:all,single,group'],
            'application_id' => ['nullable', 'integer', 'exists:aid_applications,id'],
            'group_type' => ['nullable', 'in:status,branch'],
            'group_value' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function resolveTargets(array $validated): Collection
    {
        if ($validated['target_type'] === 'single' && empty($validated['application_id'])) {
            return collect();
        }

        if ($validated['target_type'] === 'group' && (empty($validated['group_type']) || empty($validated['group_value']))) {
            return collect();
        }

        $query = AidApplication::query()->with('user:id,name,email,branch');

        if ($validated['target_type'] === 'single') {
            $query->whereKey($validated['application_id']);
        }

        if ($validated['target_type'] === 'group') {
            if ($validated['group_type'] === 'status') {
                $query->where('status', $validated['group_value']);
            }

            if ($validated['group_type'] === 'branch') {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('branch', $validated['group_value']));
            }
        }

        return $query->get()
            ->filter(fn (AidApplication $application) => (bool) $application->user?->email)
            ->unique(fn (AidApplication $application) => $application->user?->id)
            ->values();
    }
}
