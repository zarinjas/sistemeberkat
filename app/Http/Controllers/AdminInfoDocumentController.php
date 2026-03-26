<?php

namespace App\Http\Controllers;

use App\Models\InfoDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminInfoDocumentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'category' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $path = $request->file('file')->store('info-documents', 'public');

        InfoDocument::create([
            'title' => $validated['title'],
            'document_date' => $validated['document_date'],
            'category' => $validated['category'] ?? 'Info BERKAT',
            'file_path' => $path,
            'is_active' => true,
            'sort_order' => $validated['sort_order'] ?? 0,
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Dokumen PDF berjaya dimuat naik.');
    }

    public function update(Request $request, InfoDocument $infoDocument): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'category' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        if ($request->hasFile('file')) {
            if ($infoDocument->file_path && Storage::disk('public')->exists($infoDocument->file_path)) {
                Storage::disk('public')->delete($infoDocument->file_path);
            }

            $infoDocument->file_path = $request->file('file')->store('info-documents', 'public');
        }

        $infoDocument->title = $validated['title'];
        $infoDocument->document_date = $validated['document_date'];
        $infoDocument->category = $validated['category'] ?? $infoDocument->category ?? 'Info BERKAT';
        $infoDocument->sort_order = $validated['sort_order'] ?? 0;
        $infoDocument->is_active = $request->boolean('is_active');
        $infoDocument->save();

        return back()->with('success', 'Dokumen berjaya dikemaskini.');
    }

    public function destroy(InfoDocument $infoDocument): RedirectResponse
    {
        if ($infoDocument->file_path && Storage::disk('public')->exists($infoDocument->file_path)) {
            Storage::disk('public')->delete($infoDocument->file_path);
        }

        $infoDocument->delete();

        return back()->with('success', 'Dokumen berjaya dipadam.');
    }
}
