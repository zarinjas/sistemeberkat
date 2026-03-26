<?php

namespace App\Http\Controllers;

use App\Models\DashboardPoster;
use App\Models\InfoDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InfoCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $documents = InfoDocument::query()
            ->where('is_active', true)
            ->orderByDesc('document_date')
            ->orderBy('category')
            ->orderBy('title')
            ->get()
            ->map(fn (InfoDocument $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'category' => $document->category,
                'document_date' => optional($document->document_date)?->format('d M Y'),
                'file_url' => asset('storage/'.$document->file_path),
                'updated_at' => optional($document->updated_at)?->format('d M Y, h:i A'),
            ])
            ->values();

        $adminDocuments = [];
        if ($user?->isSuperAdmin()) {
            $adminDocuments = InfoDocument::query()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (InfoDocument $document) => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'document_date' => optional($document->document_date)?->format('Y-m-d'),
                    'document_date_label' => optional($document->document_date)?->format('d M Y'),
                    'is_active' => (bool) $document->is_active,
                    'file_url' => asset('storage/'.$document->file_path),
                ])
                ->values();
        }

        $adminPosters = [];
        if ($user?->isAdmin() || $user?->isSuperAdmin()) {
            $adminPosters = DashboardPoster::query()
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (DashboardPoster $poster) => [
                    'id' => $poster->id,
                    'title' => $poster->title,
                    'sort_order' => $poster->sort_order,
                    'is_active' => (bool) $poster->is_active,
                    'image_url' => asset('storage/'.$poster->image_path),
                    'updated_at' => optional($poster->updated_at)?->format('d M Y, h:i A'),
                ])
                ->values();
        }

        return Inertia::render('InfoCenter/Index', [
            'documents' => $documents,
            'adminDocuments' => $adminDocuments,
            'adminPosters' => $adminPosters,
        ]);
    }
}
