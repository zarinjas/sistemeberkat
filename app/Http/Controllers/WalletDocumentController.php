<?php

namespace App\Http\Controllers;

use App\Models\WalletDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WalletDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $documents = $request->user()
            ->walletDocuments()
            ->latest()
            ->get();

        return Inertia::render('WalletDocuments/Index', [
            'documents' => $documents,
            'types' => ['nric', 'salary_slip', 'medical_letter', 'other'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:60'],
            'label' => ['nullable', 'string', 'max:120'],
            'document' => ['required', 'file', 'max:5120'],
        ]);

        $path = $validated['document']->store('wallet-documents');

        $request->user()->walletDocuments()->create([
            'type' => $validated['type'],
            'label' => $validated['label'] ?? null,
            'file_path' => $path,
            'mime_type' => $validated['document']->getMimeType(),
            'file_size' => $validated['document']->getSize(),
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Document uploaded to wallet.');
    }

    public function destroy(Request $request, WalletDocument $walletDocument): RedirectResponse
    {
        abort_if($walletDocument->user_id !== $request->user()->id, 403);

        Storage::delete($walletDocument->file_path);
        $walletDocument->delete();

        return back()->with('success', 'Document removed from wallet.');
    }
}
