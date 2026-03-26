<?php

namespace App\Http\Controllers;

use App\Models\DashboardPoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminDashboardPosterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:ratio=1/1'],
        ]);

        $path = $request->file('image')->store('dashboard-posters', 'public');

        DashboardPoster::create([
            'title' => $validated['title'],
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => $validated['sort_order'] ?? 0,
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Poster dashboard berjaya dimuat naik.');
    }

    public function update(Request $request, DashboardPoster $dashboardPoster): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:ratio=1/1'],
        ]);

        if ($request->hasFile('image')) {
            if ($dashboardPoster->image_path && Storage::disk('public')->exists($dashboardPoster->image_path)) {
                Storage::disk('public')->delete($dashboardPoster->image_path);
            }

            $dashboardPoster->image_path = $request->file('image')->store('dashboard-posters', 'public');
        }

        $dashboardPoster->title = $validated['title'];
        $dashboardPoster->sort_order = $validated['sort_order'] ?? 0;
        $dashboardPoster->is_active = $request->boolean('is_active');
        $dashboardPoster->save();

        return back()->with('success', 'Poster dashboard berjaya dikemaskini.');
    }

    public function destroy(DashboardPoster $dashboardPoster): RedirectResponse
    {
        if ($dashboardPoster->image_path && Storage::disk('public')->exists($dashboardPoster->image_path)) {
            Storage::disk('public')->delete($dashboardPoster->image_path);
        }

        $dashboardPoster->delete();

        return back()->with('success', 'Poster dashboard berjaya dipadam.');
    }
}
