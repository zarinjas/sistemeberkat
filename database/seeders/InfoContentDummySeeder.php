<?php

namespace Database\Seeders;

use App\Models\DashboardPoster;
use App\Models\InfoDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class InfoContentDummySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedInfoDocuments();
        $this->seedDashboardPosters();
    }

    private function seedInfoDocuments(): void
    {
        $documents = [
            [
                'title' => 'Dummy - Garis Panduan Bantuan BERKAT 2026',
                'category' => 'Garis Panduan',
                'document_date' => '2026-03-01',
                'sort_order' => 1,
                'file_path' => 'info-documents/dummy-garis-panduan-2026.pdf',
            ],
            [
                'title' => 'Dummy - Senarai Semak Dokumen Sokongan',
                'category' => 'Rujukan Ahli',
                'document_date' => '2026-03-05',
                'sort_order' => 2,
                'file_path' => 'info-documents/dummy-senarai-semak.pdf',
            ],
            [
                'title' => 'Dummy - Carta Alir Permohonan Bantuan',
                'category' => 'Proses Permohonan',
                'document_date' => '2026-03-10',
                'sort_order' => 3,
                'file_path' => 'info-documents/dummy-carta-alir.pdf',
            ],
        ];

        foreach ($documents as $document) {
            $this->ensureDummyPdf($document['file_path'], $document['title']);

            InfoDocument::query()->firstOrCreate(
                ['title' => $document['title']],
                [
                    'category' => $document['category'],
                    'document_date' => $document['document_date'],
                    'file_path' => $document['file_path'],
                    'is_active' => true,
                    'sort_order' => $document['sort_order'],
                    'uploaded_by' => null,
                ]
            );
        }
    }

    private function seedDashboardPosters(): void
    {
        $posters = [
            [
                'title' => 'Dummy - Salam Ramadan BERKAT',
                'sort_order' => 1,
                'image_path' => 'dashboard-posters/dummy-poster-ramadan.png',
                'bg' => [15, 23, 42],
                'fg' => [255, 255, 255],
            ],
            [
                'title' => 'Dummy - Info Bantuan Kecemasan',
                'sort_order' => 2,
                'image_path' => 'dashboard-posters/dummy-poster-kecemasan.png',
                'bg' => [30, 64, 175],
                'fg' => [255, 255, 255],
            ],
            [
                'title' => 'Dummy - Hebahan Tarikh Penting',
                'sort_order' => 3,
                'image_path' => 'dashboard-posters/dummy-poster-hebah.png',
                'bg' => [190, 24, 93],
                'fg' => [255, 255, 255],
            ],
            [
                'title' => 'Dummy - Kemaskini Sistem Ahli',
                'sort_order' => 4,
                'image_path' => 'dashboard-posters/dummy-poster-sistem.png',
                'bg' => [22, 163, 74],
                'fg' => [255, 255, 255],
            ],
        ];

        foreach ($posters as $poster) {
            $this->ensureDummyPng(
                $poster['image_path'],
                $poster['title'],
                $poster['bg'],
                $poster['fg'],
            );

            DashboardPoster::query()->firstOrCreate(
                ['title' => $poster['title']],
                [
                    'image_path' => $poster['image_path'],
                    'is_active' => true,
                    'sort_order' => $poster['sort_order'],
                    'uploaded_by' => null,
                ]
            );
        }
    }

    private function ensureDummyPdf(string $path, string $title): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }

        $safeTitle = $this->escapePdfText($title);
        $pdf = "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            ."4 0 obj<</Length 68>>stream\nBT /F1 16 Tf 72 760 Td ({$safeTitle}) Tj ET\nendstream\nendobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \n0000000242 00000 n \n0000000360 00000 n \n"
            ."trailer<</Size 6/Root 1 0 R>>\nstartxref\n430\n%%EOF\n";

        Storage::disk('public')->put($path, $pdf);
    }

    private function ensureDummyPng(string $path, string $title, array $bgRgb, array $fgRgb): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }

        $oneByOnePngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlH0x4AAAAASUVORK5CYII=';
        Storage::disk('public')->put($path, base64_decode($oneByOnePngBase64));
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
