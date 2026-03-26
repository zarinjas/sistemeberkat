<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->validateFilters($request);

        $reportData = $this->buildReportData($request);

        $branches = User::query()
            ->whereNotNull('branch')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->values();

        $categories = AidApplication::query()
            ->whereNotNull('category_tags')
            ->get(['category_tags'])
            ->flatMap(fn (AidApplication $application) => $application->category_tags ?: [])
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Reports/Index', [
            ...$reportData,
            'filters' => [
                'branch' => $request->string('branch')->value(),
                'category' => $request->string('category')->value(),
                'fromDate' => $request->string('from_date')->value(),
                'toDate' => $request->string('to_date')->value(),
            ],
            'options' => [
                'branches' => $branches,
                'categories' => $categories,
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->validateFilters($request);

        return response()->json($this->buildReportData($request));
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'branch' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
    }

    private function buildReportData(Request $request): array
    {
        $applications = AidApplication::query()
            ->with('user:id,branch')
            ->when(
                $request->filled('from_date') && $request->filled('to_date'),
                fn ($query) => $query->whereBetween('created_at', [
                    $request->date('from_date')->startOfDay(),
                    $request->date('to_date')->endOfDay(),
                ])
            )
            ->get(['id', 'user_id', 'status', 'requested_amount', 'category_tags', 'created_at']);

        if ($request->filled('branch')) {
            $branch = $request->string('branch')->value();
            $applications = $applications->filter(fn (AidApplication $application) => ($application->user?->branch ?: 'Unassigned') === $branch)->values();
        }

        if ($request->filled('category')) {
            $category = $request->string('category')->value();
            $applications = $applications->filter(fn (AidApplication $application) => in_array($category, $application->category_tags ?: [], true))->values();
        }

        $categoryTotals = collect($applications)
            ->flatMap(function (AidApplication $application) {
                $tags = $application->category_tags ?: ['uncategorized'];

                return collect($tags)->map(fn ($tag) => [
                    'category' => $tag,
                    'amount' => (float) ($application->requested_amount ?? 0),
                ]);
            })
            ->groupBy('category')
            ->map(fn ($rows, $category) => [
                'category' => $category,
                'amount' => round($rows->sum('amount'), 2),
            ])
            ->values();

        $branchVolume = collect($applications)
            ->groupBy(fn (AidApplication $application) => $application->user?->branch ?: 'Unassigned')
            ->map(fn ($rows, $branch) => [
                'branch' => $branch,
                'count' => $rows->count(),
            ])
            ->values();

        return [
            'categoryTotals' => $categoryTotals,
            'branchVolume' => $branchVolume,
            'totalApplications' => $applications->count(),
        ];
    }
}
