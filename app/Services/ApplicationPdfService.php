<?php

namespace App\Services;

use App\Models\AidApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationPdfService
{
    public function generateAndStore(AidApplication $application): string
    {
        $application->loadMissing('user');

        $safeRef = Str::slug((string) ($application->reference_no ?: 'application-'.$application->id));
        $path = "application-pdfs/{$safeRef}-{$application->id}.pdf";

        $pdf = Pdf::loadView('pdf.application-submission', [
            'application' => $application,
            'submittedForm' => $application->buildFormPreview(),
        ])->setPaper('a4');

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
