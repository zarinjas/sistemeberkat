<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\DashboardPoster;
use App\Models\FormSchema;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user?->isAdmin() || $user?->isSuperAdmin()) {
            $pendingStatuses = [
                AidApplication::STATUS_SUBMITTED,
                AidApplication::STATUS_UNDER_REVIEW,
            ];

            $now = now();
            $currentWeekStart = $now->copy()->subDays(7);
            $previousWeekStart = $now->copy()->subDays(14);
            $previousWeekEnd = $currentWeekStart->copy();

            $newCurrent = AidApplication::query()
                ->where('created_at', '>=', $currentWeekStart)
                ->count();

            $newPrevious = AidApplication::query()
                ->whereBetween('created_at', [$previousWeekStart, $previousWeekEnd])
                ->count();

            $pendingCurrent = AidApplication::query()
                ->whereIn('status', $pendingStatuses)
                ->count();

            $pendingPrevious = AidApplication::query()
                ->whereIn('status', $pendingStatuses)
                ->whereBetween('created_at', [$previousWeekStart, $previousWeekEnd])
                ->count();

            $approvedStatuses = [
                AidApplication::STATUS_APPROVED,
                AidApplication::STATUS_DISBURSED,
            ];

            $approvedCurrent = AidApplication::query()
                ->whereIn('status', $approvedStatuses)
                ->whereBetween('updated_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count();

            $approvedPrevious = AidApplication::query()
                ->whereIn('status', $approvedStatuses)
                ->whereBetween('updated_at', [
                    $now->copy()->subMonthNoOverflow()->startOfMonth(),
                    $now->copy()->subMonthNoOverflow()->endOfMonth(),
                ])
                ->count();

            $generalQueue = AidApplication::query()
                ->with('user:id,name')
                ->whereIn('status', [
                    'pending_approval',
                    AidApplication::STATUS_SUBMITTED,
                    AidApplication::STATUS_UNDER_REVIEW,
                    AidApplication::STATUS_APPROVED,
                ])
                ->orderBy('created_at', 'asc')
                ->take(20)
                ->get()
                ->map(fn (AidApplication $application) => [
                    'id' => $application->id,
                    'referenceNo' => $application->reference_no ?: 'APP-'.$application->id,
                    'applicantName' => $application->user?->name ?: 'Tidak diketahui',
                    'category' => $this->mapCategory($application),
                    'submittedAt' => ($application->submitted_at ?: $application->created_at)->format('d M Y'),
                    'status' => $application->status === AidApplication::STATUS_APPROVED ? AidApplication::STATUS_APPROVED : 'pending_approval',
                ])
                ->values();

            return Inertia::render('Dashboard', [
                'kpiCards' => [
                    [
                        'key' => 'new',
                        'title' => 'Jumlah Permohonan Baharu',
                        'value' => $newCurrent,
                        'trend' => $this->trendText($newCurrent, $newPrevious, 'dari minggu lepas'),
                    ],
                    [
                        'key' => 'pending',
                        'title' => 'Menunggu Kelulusan (Pending)',
                        'value' => $pendingCurrent,
                        'trend' => $this->trendText($pendingCurrent, $pendingPrevious, 'dari minggu lepas'),
                    ],
                    [
                        'key' => 'approved',
                        'title' => 'Lulus Bulan Ini',
                        'value' => $approvedCurrent,
                        'trend' => $this->trendText($approvedCurrent, $approvedPrevious, 'dari bulan lepas'),
                    ],
                ],
                'urgentQueue' => [],
                'generalQueue' => $generalQueue,
                'applications' => [],
                'availableForms' => [],
                'dashboardPosters' => [],
            ]);
        }

        $applications = AidApplication::query()
            ->where('user_id', $user->id)
            ->with(['statusHistories'])
            ->latest()
            ->take(5)
            ->get();

        $availableForms = FormSchema::query()
            ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (FormSchema $schema) => [
                'id' => $schema->id,
                'title' => $schema->category_name,
                'description' => data_get($schema->schema_json, 'description', 'Permohonan untuk bantuan'),
                'status' => 'Aktif',
            ])
            ->values();

        $dashboardPosters = DashboardPoster::query()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DashboardPoster $poster) => [
                'id' => $poster->id,
                'title' => $poster->title,
                'aspect_ratio' => $poster->aspect_ratio ?: '1:1',
                'image_url' => asset('storage/'.$poster->image_path),
            ])
            ->values();

        $announcements = $user->notifications()
            ->latest()
            ->take(6)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'subject' => data_get($notification->data, 'subject', 'Notifikasi BERKAT'),
                'message' => data_get($notification->data, 'message', '-'),
                'reference_no' => data_get($notification->data, 'reference_no'),
                'status' => data_get($notification->data, 'status'),
                'image_url' => data_get($notification->data, 'image_url'),
                'created_at' => optional($notification->created_at)->format('d M Y H:i') ?: '-',
                'is_read' => (bool) $notification->read_at,
                'read_at' => $notification->read_at,
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'kpiCards' => [],
            'urgentQueue' => [],
            'generalQueue' => [],
            'applications' => $applications,
            'availableForms' => $availableForms,
            'dashboardPosters' => $dashboardPosters,
            'announcements' => $announcements,
            'unreadAnnouncementsCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function notificationsIndex(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn ($notification) => [
                'id' => $notification->id,
                'subject' => data_get($notification->data, 'subject', 'Notifikasi BERKAT'),
                'message' => data_get($notification->data, 'message', '-'),
                'reference_no' => data_get($notification->data, 'reference_no'),
                'status' => data_get($notification->data, 'status'),
                'image_url' => data_get($notification->data, 'image_url'),
                'created_at' => optional($notification->created_at)->format('d M Y H:i') ?: '-',
                'is_read' => (bool) $notification->read_at,
            ]);

        return Inertia::render('Applicant/Notifications', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAnnouncementRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return back()->with('error', 'Notifikasi tidak dijumpai.');
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notifikasi ditanda sebagai dibaca.');
    }

    public function markAllAnnouncementsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi berjaya ditanda sebagai dibaca.');
    }

    public function membershipCard(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('MembershipCard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'state' => $user->state,
                'department' => $user->department,
                'employment_grade' => $user->employment_grade,
                'member_no' => $user->member_no,
                'nric' => $user->nric,
                'profile_photo_url' => $user->profile_photo_path
                    ? asset('storage/'.$user->profile_photo_path)
                    : null,
                'initials' => strtoupper(collect(explode(' ', $user->name))->map(fn ($word) => $word[0] ?? '')->join('')),
            ],
        ]);
    }

    private function trendText(int $current, int $previous, string $suffix): string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100% '.$suffix : '0% '.$suffix;
        }

        $change = (($current - $previous) / $previous) * 100;
        $symbol = $change >= 0 ? '+' : '';

        return $symbol.(string) round($change).'% '.$suffix;
    }

    private function mapCategory(AidApplication $application): string
    {
        $firstTag = collect($application->category_tags ?: [])->first();

        if (! $firstTag) {
            return 'Umum';
        }

        return ucfirst((string) $firstTag);
    }

    private function humanElapsed($dateTime): string
    {
        if (! $dateTime) {
            return '-';
        }

        $date = $dateTime instanceof Carbon ? $dateTime : Carbon::parse($dateTime);
        $minutes = now()->diffInMinutes($date);

        if ($minutes < 60) {
            return $minutes.' minit lepas';
        }

        $hours = now()->diffInHours($date);
        if ($hours < 24) {
            return $hours.' jam lepas';
        }

        $days = now()->diffInDays($date);

        return $days.' hari lepas';
    }
}
