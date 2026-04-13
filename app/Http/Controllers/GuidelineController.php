<?php

namespace App\Http\Controllers;

use App\Models\GuidelinePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GuidelineController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $query = GuidelinePage::query()->where('slug', $slug);

        if (! $request->user()?->isSuperAdmin()) {
            $query->where('is_published', true);
        }

        $guideline = $query->firstOrFail();

        return Inertia::render('Guidelines/Show', [
            'guideline' => [
                'id' => $guideline->id,
                'title' => $guideline->title,
                'slug' => $guideline->slug,
                'html' => $this->sanitizeHtml($guideline->is_published ? ($guideline->published_html ?: $guideline->draft_html) : $guideline->draft_html),
                'is_published' => (bool) $guideline->is_published,
                'published_at' => optional($guideline->published_at)?->format('d M Y, h:i A'),
                'updated_at' => optional($guideline->updated_at)?->format('d M Y, h:i A'),
            ],
        ]);
    }

    public function manage(Request $request): Response
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $pages = GuidelinePage::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GuidelinePage $page) => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'draft_html' => $this->sanitizeHtml($page->draft_html ?: ''),
                'published_html' => $this->sanitizeHtml($page->published_html ?: ''),
                'is_published' => (bool) $page->is_published,
                'sort_order' => (int) $page->sort_order,
                'published_at' => optional($page->published_at)?->format('d M Y, h:i A'),
                'updated_at' => optional($page->updated_at)?->format('d M Y, h:i A'),
            ])
            ->values()
            ->all();

        return Inertia::render('Guidelines/Manage', [
            'pages' => $pages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'draft_html' => ['required', 'string'],
        ]);

        $page = GuidelinePage::create([
            'title' => $validated['title'],
            'slug' => $this->generateUniqueSlug($validated['title']),
            'draft_html' => $this->sanitizeHtml($validated['draft_html']),
            'is_published' => false,
            'sort_order' => ((int) GuidelinePage::query()->max('sort_order')) + 1,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('guidelines.manage')
            ->with('success', "Halaman garis panduan '{$page->title}' berjaya dicipta.");
    }

    public function update(Request $request, GuidelinePage $guidelinePage): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'draft_html' => ['required', 'string'],
        ]);

        $newSlug = $guidelinePage->title === $validated['title']
            ? $guidelinePage->slug
            : $this->generateUniqueSlug($validated['title'], $guidelinePage->id);

        $guidelinePage->update([
            'title' => $validated['title'],
            'slug' => $newSlug,
            'draft_html' => $this->sanitizeHtml($validated['draft_html']),
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Draf garis panduan berjaya disimpan.');
    }

    public function publish(Request $request, GuidelinePage $guidelinePage): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'draft_html' => ['required', 'string'],
        ]);

        $newSlug = $guidelinePage->title === $validated['title']
            ? $guidelinePage->slug
            : $this->generateUniqueSlug($validated['title'], $guidelinePage->id);

        $sanitizedHtml = $this->sanitizeHtml($validated['draft_html']);

        $guidelinePage->update([
            'title' => $validated['title'],
            'slug' => $newSlug,
            'draft_html' => $sanitizedHtml,
            'published_html' => $sanitizedHtml,
            'is_published' => true,
            'published_at' => now(),
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Garis panduan berjaya diterbitkan.');
    }

    public function unpublish(Request $request, GuidelinePage $guidelinePage): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $guidelinePage->update([
            'is_published' => false,
            'published_at' => null,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Garis panduan telah dinyahterbitkan.');
    }

    public function destroy(Request $request, GuidelinePage $guidelinePage): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $guidelinePage->delete();

        return back()->with('success', 'Halaman garis panduan berjaya dipadam.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'exists:guideline_pages,id'],
        ]);

        foreach (array_values($validated['ordered_ids']) as $index => $id) {
            GuidelinePage::query()->where('id', $id)->update([
                'sort_order' => $index + 1,
                'updated_by' => $request->user()?->id,
            ]);
        }

        return back()->with('success', 'Susunan garis panduan berjaya dikemaskini.');
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (
            GuidelinePage::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function sanitizeHtml(string $html): string
    {
        $value = trim($html);

        if ($value !== '' && (str_contains($value, '&lt;') || str_contains($value, '&gt;'))) {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $cleaned = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value) ?? '';

        return trim($cleaned);
    }
}
