<?php

namespace App\Http\Controllers;

use App\Models\DashboardPoster;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InfoCenterController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('info-center.infographics');
    }

    public function infographics(Request $request): Response
    {
        return Inertia::render('InfoCenter/Infographics', [
            'adminPosters' => $this->fetchAdminPosters($request),
            'posters' => $this->fetchPublishedPosters(),
        ]);
    }

    public function legal(Request $request): Response
    {
        return Inertia::render('InfoCenter/Legal', [
            'legalContent' => $this->fetchLegalContent(),
        ]);
    }

    public function ajk(Request $request): Response
    {
        return Inertia::render('InfoCenter/Ajk', [
            'ajkContent' => $this->fetchAjkContent(),
        ]);
    }

    public function saveLegalDraft(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'draft_html' => ['required', 'string'],
        ]);

        $normalizedHtml = $this->normalizeRichHtml($validated['draft_html']);

        SiteSetting::set('info_legal_title', $validated['title']);
        SiteSetting::set('info_legal_draft_html', $normalizedHtml);
        SiteSetting::set('info_legal_draft_updated_at', now()->toDateTimeString());
        SiteSetting::set('info_legal_draft_updated_by_name', $request->user()->name);

        return back()->with('success', 'Draf undang-undang berjaya disimpan.');
    }

    public function publishLegalContent(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'draft_html' => ['required', 'string'],
        ]);

        $normalizedHtml = $this->normalizeRichHtml($validated['draft_html']);

        SiteSetting::set('info_legal_title', $validated['title']);
        SiteSetting::set('info_legal_draft_html', $normalizedHtml);
        SiteSetting::set('info_legal_published_html', $normalizedHtml);
        SiteSetting::set('info_legal_published_at', now()->toDateTimeString());
        SiteSetting::set('info_legal_published_by_name', $request->user()->name);

        return back()->with('success', 'Undang-undang & perlembagaan berjaya diterbitkan.');
    }

    public function uploadAjkImage(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:png', 'max:5120'],
        ]);

        $oldPath = SiteSetting::get('info_ajk_image_path');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $validated['image']->store('info-center/ajk', 'public');

        SiteSetting::set('info_ajk_image_path', $path);
        SiteSetting::set('info_ajk_updated_at', now()->toDateTimeString());
        SiteSetting::set('info_ajk_updated_by_name', $request->user()->name);

        return back()->with('success', 'Imej Senarai AJK berjaya dimuat naik.');
    }

    public function removeAjkImage(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $oldPath = SiteSetting::get('info_ajk_image_path');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        SiteSetting::set('info_ajk_image_path', '');
        SiteSetting::set('info_ajk_updated_at', now()->toDateTimeString());
        SiteSetting::set('info_ajk_updated_by_name', $request->user()->name);

        return back()->with('success', 'Imej Senarai AJK berjaya dibuang.');
    }

    private function fetchAdminPosters(Request $request): array
    {
        $user = $request->user();

        $adminPosters = [];
        if ($user?->isAdmin() || $user?->isSuperAdmin()) {
            $adminPosters = DashboardPoster::query()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (DashboardPoster $poster) => [
                    'id' => $poster->id,
                    'title' => $poster->title,
                    'is_active' => (bool) $poster->is_active,
                    'image_url' => asset('storage/'.$poster->image_path),
                    'updated_at' => optional($poster->updated_at)?->format('d M Y, h:i A'),
                ])
                ->values()
                ->all();
        }

        return $adminPosters;
    }

    private function fetchPublishedPosters(): array
    {
        return DashboardPoster::query()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DashboardPoster $poster) => [
                'id' => $poster->id,
                'title' => $poster->title,
                'image_url' => asset('storage/'.$poster->image_path),
            ])
            ->values()
            ->all();
    }

    private function fetchLegalContent(): array
    {
        return [
            'title' => SiteSetting::get('info_legal_title', 'Undang-Undang & Perlembagaan BERKAT'),
            'draft_html' => $this->normalizeRichHtml(SiteSetting::get('info_legal_draft_html', '')),
            'published_html' => $this->normalizeRichHtml(SiteSetting::get('info_legal_published_html', '')),
            'published_at' => SiteSetting::get('info_legal_published_at'),
            'published_by' => SiteSetting::get('info_legal_published_by_name', ''),
        ];
    }

    private function fetchAjkContent(): array
    {
        return [
            'image_url' => $this->resolveStorageAsset(SiteSetting::get('info_ajk_image_path')),
            'updated_at' => SiteSetting::get('info_ajk_updated_at'),
            'updated_by' => SiteSetting::get('info_ajk_updated_by_name', ''),
        ];
    }

    private function resolveStorageAsset(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    }

    private function normalizeRichHtml(?string $html): string
    {
        $value = trim((string) $html);

        if ($value === '') {
            return '';
        }

        if (str_contains($value, '&lt;') || str_contains($value, '&gt;')) {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value) ?? '';

        return trim($value);
    }
}
