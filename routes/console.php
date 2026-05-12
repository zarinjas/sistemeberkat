<?php

use App\Support\DummyDataBatchGenerator;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('berkat:seed-dummy
    {--members=30 : Bilangan ahli ujian}
    {--applications=60 : Bilangan permohonan ujian}
    {--tag= : Tag batch ujian. Jika kosong, sistem jana automatik}
    {--password=password : Kata laluan login untuk semua ahli ujian}
    {--dry-run : Papar ringkasan tanpa simpan ke database}', function (DummyDataBatchGenerator $generator) {
    $members = max(0, (int) $this->option('members'));
    $applications = max(0, (int) $this->option('applications'));
    $password = (string) $this->option('password');
    $tag = (string) ($this->option('tag') ?: ('UJIAN-'.now()->format('YmdHis')));

    if ($password === '') {
        $this->error('Password ujian tidak boleh kosong.');

        return 1;
    }

    try {
        $tag = $generator->normalizeTag($tag);
    } catch (\InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    if ($this->option('dry-run')) {
        $this->table(
            ['Perkara', 'Nilai'],
            [
                ['Tag batch', $tag],
                ['Ahli ujian', (string) $members],
                ['Permohonan ujian', (string) $applications],
                ['Password login', $password],
            ]
        );

        $this->comment('Dry run sahaja. Tiada data ditulis ke database.');

        return 0;
    }

    try {
        $result = $generator->seed($members, $applications, $tag, $password);
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    $this->info('Batch ujian berjaya dicipta.');
    $this->table(
        ['Perkara', 'Nilai'],
        [
            ['Tag batch', $result['tag']],
            ['Ahli dicipta', (string) $result['members_created']],
            ['Permohonan dicipta', (string) $result['applications_created']],
            ['Password login', $result['login_password']],
            ['Admin/operator rujukan', $result['operator_email'] ?: '-'],
            ['Borang aktif dijumpai', (string) $result['forms_used']],
        ]
    );

    $this->comment('Cari rekod ujian melalui tag ini pada email, member no, dan reference no.');

    return 0;
})->purpose('Cipta batch ahli ujian dan permohonan ujian yang selamat untuk ujian di VPS');

Artisan::command('berkat:purge-dummy
    {tag : Tag batch ujian yang mahu dipadam}
    {--dry-run : Papar ringkasan tanpa padam}', function (DummyDataBatchGenerator $generator) {
    $tag = (string) $this->argument('tag');

    try {
        $normalizedTag = $generator->normalizeTag($tag);
        $summary = $generator->purge($normalizedTag, ! $this->option('dry-run'));
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    $this->table(
        ['Perkara', 'Nilai'],
        [
            ['Tag batch', $summary['tag']],
            ['Ahli ditemui', (string) $summary['members_found']],
            ['Permohonan ditemui', (string) $summary['applications_found']],
            ['Status history ditemui', (string) $summary['status_histories_found']],
        ]
    );

    if ($this->option('dry-run')) {
        $this->comment('Dry run sahaja. Tiada data dipadam.');

        return 0;
    }

    $this->info('Batch ujian selesai dipadam.');

    return 0;
})->purpose('Padam batch ujian berdasarkan tag');
