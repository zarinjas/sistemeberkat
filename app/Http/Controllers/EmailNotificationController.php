<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\NotificationBlast;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmailNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureBroadcaster($request);

        $historyKind = $request->string('history_kind')->value() ?: 'all';
        $historyModule = $request->string('history_module')->value() ?: 'all';
        $historySenderId = (int) $request->integer('history_sender_id');
        $historyFrom = $request->string('history_from')->value();
        $historyTo = $request->string('history_to')->value();
        $historySearch = trim((string) $request->string('history_q')->value());

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

        $historyQuery = NotificationBlast::query()
            ->with('sender:id,name')
            ->when($historyKind !== 'all', fn ($query) => $query->whereJsonContains('target_meta->notification_kind', $historyKind))
            ->when($historyModule !== 'all', fn ($query) => $query->whereJsonContains('target_meta->source_module', $historyModule))
            ->when($historySenderId > 0, fn ($query) => $query->where('sent_by_user_id', $historySenderId))
            ->when($historyFrom !== '', fn ($query) => $query->whereDate('sent_at', '>=', $historyFrom))
            ->when($historyTo !== '', fn ($query) => $query->whereDate('sent_at', '<=', $historyTo))
            ->when($historySearch !== '', fn ($query) => $query->where(function ($inner) use ($historySearch) {
                $inner->where('subject', 'like', "%{$historySearch}%")
                    ->orWhere('message', 'like', "%{$historySearch}%");
            }));

        $history = $historyQuery
            ->latest('sent_at')
            ->take(60)
            ->get()
            ->map(fn (NotificationBlast $blast) => [
                'id' => $blast->id,
                'subject' => $blast->subject,
                'message' => $blast->message,
                'target_type' => $blast->target_type,
                'target_meta' => $blast->target_meta,
                'notification_kind' => data_get($blast->target_meta, 'notification_kind', 'general'),
                'source_module' => data_get($blast->target_meta, 'source_module', 'notifications'),
                'image_url' => $blast->image_path ? asset('storage/'.$blast->image_path) : null,
                'channels' => $blast->channels ?: [],
                'recipient_count' => $blast->recipient_count,
                'sent_by' => $blast->sender?->name ?: 'Sistem',
                'sent_at' => optional($blast->sent_at)->format('d M Y H:i') ?: '-',
            ])
            ->values();

        $historySenderOptions = NotificationBlast::query()
            ->with('sender:id,name')
            ->whereNotNull('sent_by_user_id')
            ->select('sent_by_user_id')
            ->distinct()
            ->get()
            ->map(fn (NotificationBlast $blast) => [
                'id' => (int) $blast->sent_by_user_id,
                'name' => $blast->sender?->name ?: 'Tidak diketahui',
            ])
            ->sortBy('name')
            ->values();

        return Inertia::render('Notifications/Index', [
            'groups' => [
                'status' => $statusGroups,
                'branch' => $branchGroups,
            ],
            'history' => $history,
            'historyFilters' => [
                'kind' => $historyKind,
                'module' => $historyModule,
                'sender_id' => $historySenderId,
                'from' => $historyFrom,
                'to' => $historyTo,
                'q' => $historySearch,
            ],
            'historySenderOptions' => $historySenderOptions,
        ]);
    }

    public function send(Request $request, AidApplication $application): RedirectResponse
    {
        $this->ensureBroadcaster($request);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'notification_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'channels' => ['nullable', 'array', 'min:1'],
            'channels.*' => ['in:mail,database'],
        ]);

        if (! $application->user) {
            return back()->with('error', 'Pemohon tidak dijumpai.');
        }

        $channels = $this->resolveChannels($validated['channels'] ?? null);

        if (in_array('mail', $channels, true) && ! $application->user?->email) {
            return back()->with('error', 'Emel pemohon tidak dijumpai untuk penghantaran emel.');
        }

        $imagePath = $request->hasFile('notification_image')
            ? $request->file('notification_image')->store('notification-images', 'public')
            : null;

        $this->dispatchNotifications(
            targets: collect([$application]),
            subject: $validated['subject'],
            message: $validated['message'],
            channels: $channels,
            senderUserId: $request->user()->id,
            targetType: 'single',
            imagePath: $imagePath,
            targetMeta: [
                'notification_kind' => 'general',
                'source_module' => 'notifications',
                'application_id' => $application->id,
                'reference_no' => $application->reference_no,
            ],
        );

        return back()->with('success', 'Notifikasi berjaya dihantar kepada pemohon.');
    }

    public function sendBulk(Request $request): RedirectResponse
    {
        $this->ensureBroadcaster($request);

        $validated = $request->validate([
            ...$this->targetValidationRules(),
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'notification_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'channels' => ['nullable', 'array', 'min:1'],
            'channels.*' => ['in:mail,database'],
        ]);

        $targets = $this->resolveTargets($validated);

        if ($targets->isEmpty()) {
            return back()->with('error', 'Tiada penerima sah untuk sasaran yang dipilih.');
        }

        $channels = $this->resolveChannels($validated['channels'] ?? null);
        $imagePath = $request->hasFile('notification_image')
            ? $request->file('notification_image')->store('notification-images', 'public')
            : null;

        $this->dispatchNotifications(
            targets: $targets,
            subject: $validated['subject'],
            message: $validated['message'],
            channels: $channels,
            senderUserId: $request->user()->id,
            targetType: $validated['target_type'],
            imagePath: $imagePath,
            targetMeta: [
                'notification_kind' => 'general',
                'source_module' => 'notifications',
                'group_type' => $validated['group_type'] ?? null,
                'group_value' => $validated['group_value'] ?? null,
                'application_id' => $validated['application_id'] ?? null,
            ],
        );

        return back()->with('success', 'Notifikasi berjaya dihantar kepada '.$targets->count().' penerima.');
    }

    public function previewCount(Request $request): JsonResponse
    {
        $this->ensureBroadcaster($request);

        $validated = $request->validate($this->targetValidationRules());

        $targets = $this->resolveTargets($validated);

        return response()->json([
            'count' => $targets->count(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->ensureBroadcaster($request);

        $validated = $request->validate([
            ...$this->targetValidationRules(),
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'channels' => ['nullable', 'array', 'min:1'],
            'channels.*' => ['in:mail,database'],
        ]);

        $targets = $this->resolveTargets($validated);
        $channels = $this->resolveChannels($validated['channels'] ?? null);

        $uniqueTargets = $targets
            ->filter(fn (AidApplication $application) => (bool) $application->user)
            ->unique(fn (AidApplication $application) => (int) $application->user->id)
            ->values();

        $sampleRecipients = $uniqueTargets
            ->take(8)
            ->map(fn (AidApplication $application) => [
                'application_id' => $application->id,
                'reference_no' => $application->reference_no ?: 'APP-'.$application->id,
                'name' => $application->user?->name ?: 'Tidak diketahui',
                'email' => $application->user?->email,
                'branch' => $application->user?->branch,
                'status' => $application->status,
                'effective_channels' => collect($channels)
                    ->reject(fn (string $channel) => $channel === 'mail' && ! $application->user?->email)
                    ->values()
                    ->all(),
            ])
            ->values();

        return response()->json([
            'count' => $uniqueTargets->count(),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'channels' => $channels,
            'sample_recipients' => $sampleRecipients,
            'sample_truncated' => $uniqueTargets->count() > $sampleRecipients->count(),
        ]);
    }

    private function ensureBroadcaster(Request $request): void
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->isSuperAdmin())) {
            abort(403, 'Hanya admin atau superadmin boleh menghantar notifikasi blast.');
        }
    }

    private function resolveChannels(?array $channels): array
    {
        $resolved = collect($channels ?: ['mail', 'database'])
            ->filter(fn ($channel) => in_array($channel, ['mail', 'database'], true))
            ->unique()
            ->values()
            ->all();

        return count($resolved) ? $resolved : ['database'];
    }

    private function dispatchNotifications(
        Collection $targets,
        string $subject,
        string $message,
        array $channels,
        int $senderUserId,
        string $targetType,
        ?string $imagePath = null,
        array $targetMeta = [],
    ): void {
        $imageUrl = $imagePath ? Storage::disk('public')->url($imagePath) : null;

        $uniqueTargets = $targets
            ->filter(fn (AidApplication $application) => (bool) $application->user)
            ->unique(fn (AidApplication $application) => (int) $application->user->id)
            ->values();

        foreach ($uniqueTargets as $application) {
            $application->user->notify(new ApplicationStatusNotification(
                application: $application,
                subject: $subject,
                message: $message,
                channels: $channels,
                imageUrl: $imageUrl,
            ));
        }

        NotificationBlast::create([
            'sent_by_user_id' => $senderUserId,
            'target_type' => $targetType,
            'target_meta' => $targetMeta,
            'subject' => $subject,
            'message' => $message,
            'image_path' => $imagePath,
            'channels' => $channels,
            'recipient_count' => $uniqueTargets->count(),
            'recipient_user_ids' => $uniqueTargets->map(fn (AidApplication $application) => (int) $application->user->id)->all(),
            'sent_at' => now(),
        ]);
    }

    private function targetValidationRules(): array
    {
        return [
            'target_type' => ['required', 'in:all,group'],
            'group_type' => ['nullable', 'in:status,branch'],
            'group_value' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function resolveTargets(array $validated): Collection
    {
        if ($validated['target_type'] === 'group' && (empty($validated['group_type']) || empty($validated['group_value']))) {
            return collect();
        }

        $query = AidApplication::query()->with('user:id,name,email,branch');

        if ($validated['target_type'] === 'group') {
            if ($validated['group_type'] === 'status') {
                $query->where('status', $validated['group_value']);
            }

            if ($validated['group_type'] === 'branch') {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('branch', $validated['group_value']));
            }
        }

        return $query->get()
            ->filter(fn (AidApplication $application) => (bool) $application->user)
            ->values();
    }
}
