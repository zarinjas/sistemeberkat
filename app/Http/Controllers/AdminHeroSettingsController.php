<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminHeroSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/HeroSettings', [
            'heroConfig' => [
                'image_url' => SiteSetting::get('login_hero_image_url', ''),
                'heading' => SiteSetting::get('login_hero_heading', 'Selamat Datang ke e-BERKAT!'),
                'subtext' => SiteSetting::get('login_hero_subtext', 'Sistem Pengurusan Bantuan Digital Bersepadu yang pintar, pantas, dan telus.'),
                'overlay_color' => SiteSetting::get('login_hero_overlay_color', '#020617'),
                'overlay_opacity' => (int) SiteSetting::get('login_hero_overlay_opacity', 60),
                'logo_url' => SiteSetting::get('app_logo_url', ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'heading' => 'required|string|max:255',
            'subtext' => 'required|string|max:500',
            'overlay_color' => 'required|string|max:20',
            'overlay_opacity' => 'required|integer|min:10|max:90',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|dimensions:min_width=1200,min_height=800',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
        ]);

        SiteSetting::set('login_hero_heading', $request->heading);
        SiteSetting::set('login_hero_subtext', $request->subtext);
        SiteSetting::set('login_hero_overlay_color', $request->overlay_color);
        SiteSetting::set('login_hero_overlay_opacity', $request->overlay_opacity);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            $oldImage = SiteSetting::get('login_hero_image_url');
            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }

            // Store new image
            $path = $request->file('image')->store('hero-images', 'public');
            SiteSetting::set('login_hero_image_url', $path);
        }

        if ($request->hasFile('logo')) {
            $oldLogo = SiteSetting::get('app_logo_url');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $logoPath = $request->file('logo')->store('logos', 'public');
            SiteSetting::set('app_logo_url', $logoPath);
        }

        return redirect()->back()->with('success', 'Tetapan hero berjaya dikemaskini.');
    }

    public function removeImage()
    {
        $oldImage = SiteSetting::get('login_hero_image_url');
        if ($oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        SiteSetting::set('login_hero_image_url', '');

        return redirect()->back()->with('success', 'Gambar hero berjaya dibuang.');
    }
}
