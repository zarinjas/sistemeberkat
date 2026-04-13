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
        $this->ensureAdminOrSuperadmin($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'image.max' => 'Saiz imej tidak boleh melebihi 10MB.',
            'image.image' => 'Fail yang dimuat naik mestilah format imej.',
            'image.mimes' => 'Hanya format JPG, JPEG, PNG, atau WEBP dibenarkan.',
            'image.uploaded' => 'Gagal memuat naik imej. Sila semak had saiz fail pada konfigurasi pelayan (PHP upload_max_filesize).',
            'title.required' => 'Tajuk poster wajib diisi.',
            'image.required' => 'Imej poster wajib dimuat naik.',
        ]);

        $detectedRatio = $this->detectAspectRatio($request->file('image')->getPathname());

        $path = $request->file('image')->store('dashboard-posters', 'public');

        DashboardPoster::create([
            'title' => $validated['title'],
            'image_path' => $path,
            'aspect_ratio' => $detectedRatio,
            'is_active' => true,
            'sort_order' => 0,
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Poster dashboard berjaya dimuat naik.');
    }

    public function update(Request $request, DashboardPoster $dashboardPoster): RedirectResponse
    {
        $this->ensureAdminOrSuperadmin($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'image.max' => 'Saiz imej tidak boleh melebihi 10MB.',
            'image.image' => 'Fail yang dimuat naik mestilah format imej.',
            'image.mimes' => 'Hanya format JPG, JPEG, PNG, atau WEBP dibenarkan.',
            'image.uploaded' => 'Gagal memuat naik imej. Sila semak had saiz fail pada konfigurasi pelayan (PHP upload_max_filesize).',
        ]);

        if ($request->hasFile('image')) {
            if ($dashboardPoster->image_path && Storage::disk('public')->exists($dashboardPoster->image_path)) {
                Storage::disk('public')->delete($dashboardPoster->image_path);
            }

            $dashboardPoster->image_path = $request->file('image')->store('dashboard-posters', 'public');
            $dashboardPoster->aspect_ratio = $this->detectAspectRatio($request->file('image')->getPathname());
        }

        $dashboardPoster->title = $validated['title'];
        $dashboardPoster->is_active = $request->boolean('is_active');
        $dashboardPoster->save();

        return back()->with('success', 'Poster dashboard berjaya dikemaskini.');
    }

    public function destroy(DashboardPoster $dashboardPoster): RedirectResponse
    {
        $this->ensureAdminOrSuperadmin(request());

        if ($dashboardPoster->image_path && Storage::disk('public')->exists($dashboardPoster->image_path)) {
            Storage::disk('public')->delete($dashboardPoster->image_path);
        }

        $dashboardPoster->delete();

        return back()->with('success', 'Poster dashboard berjaya dipadam.');
    }

    public function approveApplication($applicationId, Request $request): RedirectResponse
    {
        $application = \App\Models\AidApplication::findOrFail($applicationId);

        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $application->update([
            'status' => 'approved',
            'approved_amount' => $validated['approved_amount'],
            'approved_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Permohonan berjaya diluluskan.');
    }

    public function markAsPaid($applicationId, Request $request): RedirectResponse
    {
        $application = \App\Models\AidApplication::findOrFail($applicationId);

        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'max:255'],
        ]);

        $application->update([
            'status' => 'paid',
            'payment_reference' => $validated['payment_reference'],
            'payment_date' => now(),
        ]);

        return back()->with('success', 'Pembayaran berjaya ditandakan.');
    }

    private function detectAspectRatio(string $imagePath): string
    {
        [$width, $height] = getimagesize($imagePath);

        $width = max(1, (int) $width);
        $height = max(1, (int) $height);
        $gcd = $this->greatestCommonDivisor($width, $height);

        return sprintf('%d:%d', intdiv($width, $gcd), intdiv($height, $gcd));
    }

    private function greatestCommonDivisor(int $a, int $b): int
    {
        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return max($a, 1);
    }

    private function ensureAdminOrSuperadmin(Request $request): void
    {
        $user = $request->user();

        abort_unless($user?->isAdmin() || $user?->isSuperAdmin(), 403);
    }
}
