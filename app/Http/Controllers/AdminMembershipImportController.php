<?php

namespace App\Http\Controllers;

use App\Models\MemberOperationAudit;
use App\Models\User;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminMembershipImportController extends Controller
{
    private const CSV_COLUMNS = [
        'nric',
        'name',
        'email',
        'phone',
        'member_no',
        'job_title',
        'department',
        'state',
    ];

    public function downloadTemplate(Request $request): HttpResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin dibenarkan untuk muat turun template import.');
        }

        $filename = 'template-membership-import.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = static function (): void {
            $output = fopen('php://output', 'w');

            if (! $output) {
                return;
            }

            fputcsv($output, self::CSV_COLUMNS);
            fputcsv($output, [
                '900101101010',
                'Ali Bin Abu',
                'ali.abubakar@example.com',
                '0123456789',
                'BERKAT-6489722',
                'Eksekutif',
                'Operasi',
                'Selangor',
            ]);

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin dibenarkan untuk import ahli.');
        }

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return back()->with('error', 'Fail CSV tidak dapat dibaca.');
        }

        $headerRow = fgetcsv($handle);
        if (! $headerRow) {
            fclose($handle);

            return back()->with('error', 'CSV kosong atau format tidak sah.');
        }

        $headers = collect($headerRow)
            ->map(fn ($column) => Str::of((string) $column)->trim()->lower()->replace(' ', '_')->value())
            ->values()
            ->all();

        if (! in_array('nric', $headers, true) || ! in_array('name', $headers, true)) {
            fclose($handle);

            return back()->with('error', 'CSV mesti mengandungi kolum wajib: nric, name.');
        }

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $summary['processed']++;

            $values = array_pad($row, count($headers), null);
            $data = array_combine($headers, $values) ?: [];

            $nric = preg_replace('/\D+/', '', (string) ($data['nric'] ?? ''));
            $name = trim((string) ($data['name'] ?? ''));

            if ($nric === '' || $name === '') {
                $summary['skipped']++;
                $summary['errors'][] = "Baris {$summary['processed']}: nric atau name kosong.";
                continue;
            }

            $user = User::query()->where('nric', $nric)->first();
            $isNew = ! $user;

            if (! $user) {
                $user = new User();
                $user->nric = $nric;
                $user->role = 'applicant';
                $user->first_login_completed = false;
                $user->password = Hash::make(Str::random(24));
            }

            $email = strtolower(trim((string) ($data['email'] ?? '')));
            if ($email !== '') {
                $existingEmailOwner = User::query()
                    ->where('email', $email)
                    ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
                    ->exists();

                if ($existingEmailOwner) {
                    $summary['skipped']++;
                    $summary['errors'][] = "Baris {$summary['processed']}: e-mel {$email} telah digunakan pengguna lain.";
                    continue;
                }

                $user->email = $email;
            } elseif (! $user->email) {
                $user->email = "nric{$nric}@pending.local";
            }

            $memberNo = trim((string) ($data['member_no'] ?? ''));
            if ($memberNo !== '') {
                $existingMemberNoOwner = User::query()
                    ->where('member_no', $memberNo)
                    ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
                    ->exists();

                if ($existingMemberNoOwner) {
                    $summary['skipped']++;
                    $summary['errors'][] = "Baris {$summary['processed']}: member_no {$memberNo} telah digunakan pengguna lain.";
                    continue;
                }

                $user->member_no = $memberNo;
            }

            $user->name = $name;
            $user->phone = trim((string) ($data['phone'] ?? '')) ?: $user->phone;
            $user->job_title = trim((string) ($data['job_title'] ?? '')) ?: $user->job_title;
            $user->department = trim((string) ($data['department'] ?? '')) ?: $user->department;
            $user->state = trim((string) ($data['state'] ?? '')) ?: $user->state;

            $user->save();

            if ($isNew) {
                $summary['created']++;
            } else {
                $summary['updated']++;
            }
        }

        fclose($handle);

        MemberOperationAudit::create([
            'actor_user_id' => (int) $request->user()->id,
            'member_user_id' => null,
            'action' => 'member_csv_import',
            'context' => [
                'filename' => $file?->getClientOriginalName(),
                'processed' => (int) ($summary['processed'] ?? 0),
                'created' => (int) ($summary['created'] ?? 0),
                'updated' => (int) ($summary['updated'] ?? 0),
                'skipped' => (int) ($summary['skipped'] ?? 0),
                'error_count' => count($summary['errors'] ?? []),
            ],
        ]);

        return back()
            ->with('success', 'Import CSV ahli selesai diproses.')
            ->with('import_summary', $summary);
    }
}
