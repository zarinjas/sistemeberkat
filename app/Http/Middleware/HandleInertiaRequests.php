<?php

namespace App\Http\Middleware;

use App\Models\GuidelinePage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'branding' => [
                'logo_url' => function () {
                    $path = SiteSetting::get('app_logo_url');

                    return $path ? asset('storage/'.$path) : null;
                },
            ],
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'role' => $request->user()->role,
                        'phone' => $request->user()->phone,
                        'job_title' => $request->user()->job_title,
                        'state' => $request->user()->state,
                        'department' => $request->user()->department,
                        'profile_photo_url' => $request->user()->profile_photo_path
                            ? asset('storage/'.$request->user()->profile_photo_path)
                            : null,
                        'branch' => $request->user()->branch,
                        'is_superadmin' => $request->user()->isSuperAdmin(),
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'guidelinesNav' => fn () => Schema::hasTable('guideline_pages')
                ? GuidelinePage::query()
                    ->where('is_published', true)
                    ->orderBy('sort_order')
                    ->orderByDesc('published_at')
                    ->orderByDesc('created_at')
                    ->get(['title', 'slug'])
                    ->map(fn (GuidelinePage $page) => [
                        'title' => $page->title,
                        'slug' => $page->slug,
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }
}
