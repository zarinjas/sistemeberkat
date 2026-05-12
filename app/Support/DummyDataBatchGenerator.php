<?php

namespace App\Support;

use App\Models\AidApplication;
use App\Models\ApplicationStatusHistory;
use App\Models\FormSchema;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataBatchGenerator
{
    public function seed(int $memberCount, int $applicationCount, string $tag, string $password): array
    {
        $tag = $this->normalizeTag($tag);

        if ($memberCount < 0 || $applicationCount < 0) {
            throw new \InvalidArgumentException('Jumlah ahli dan permohonan mestilah 0 atau lebih.');
        }

        if ($applicationCount > 0 && $memberCount === 0) {
            throw new \InvalidArgumentException('Permohonan dummy memerlukan sekurang-kurangnya seorang ahli dummy.');
        }

        $operator = User::query()
            ->whereIn('role', ['superadmin', 'admin'])
            ->orderByRaw("CASE WHEN role = 'superadmin' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        $forms = FormSchema::query()
            ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->values();

        $createdMemberIds = [];
        $createdApplicationIds = [];

        DB::transaction(function () use (
            $memberCount,
            $applicationCount,
            $tag,
            $password,
            $operator,
            $forms,
            &$createdMemberIds,
            &$createdApplicationIds
        ): void {
            $members = collect();

            for ($index = 1; $index <= $memberCount; $index++) {
                $member = User::query()->create($this->buildUserPayload($tag, $index, $password));
                $members->push($member);
                $createdMemberIds[] = (int) $member->id;
            }

            for ($index = 1; $index <= $applicationCount; $index++) {
                /** @var User $member */
                $member = $members[($index - 1) % $members->count()];
                $form = $forms->isNotEmpty() ? $forms[($index - 1) % $forms->count()] : null;
                $status = $this->resolveStatus($index);
                $timestamps = $this->buildStatusTimestamps($status, $index);
                $content = $this->buildApplicationContent($form, $index);

                $application = AidApplication::query()->create([
                    'user_id' => $member->id,
                    'reference_no' => sprintf('%s-A%04d', $tag, $index),
                    'status' => $status,
                    'triage_answers' => $content['triage_answers'],
                    'dynamic_payload' => $content['dynamic_payload'],
                    'category_tags' => $content['category_tags'],
                    'requested_amount' => $content['requested_amount'],
                    'paid_amount' => in_array($status, [AidApplication::STATUS_APPROVED, AidApplication::STATUS_DISBURSED], true)
                        ? $content['requested_amount']
                        : null,
                    'transaction_ref' => $status === AidApplication::STATUS_DISBURSED
                        ? sprintf('TXN-%s-%04d', $tag, $index)
                        : null,
                    'payment_prepared_by_user_id' => $status === AidApplication::STATUS_DISBURSED ? $operator?->id : null,
                    'payment_approved_by_user_id' => $status === AidApplication::STATUS_DISBURSED ? $operator?->id : null,
                    'priority_score' => $content['priority_score'],
                    'priority_label' => $content['priority_label'],
                    'priority_reason' => $content['priority_reason'],
                    'submitted_at' => $timestamps['submitted_at'],
                    'reviewed_at' => $timestamps['reviewed_at'],
                    'decided_at' => $timestamps['decided_at'],
                    'paid_at' => $timestamps['paid_at'],
                    'payment_prepared_at' => $timestamps['payment_prepared_at'],
                    'payment_approved_at' => $timestamps['payment_approved_at'],
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ]);

                ApplicationStatusHistory::query()->create([
                    'aid_application_id' => $application->id,
                    'from_status' => null,
                    'to_status' => $status,
                    'changed_by_user_id' => $operator?->id,
                    'notes' => 'Dummy application generated via artisan command.',
                    'changed_at' => $timestamps['updated_at'],
                    'created_at' => $timestamps['updated_at'],
                    'updated_at' => $timestamps['updated_at'],
                ]);

                $createdApplicationIds[] = (int) $application->id;
            }
        });

        return [
            'tag' => $tag,
            'members_created' => count($createdMemberIds),
            'applications_created' => count($createdApplicationIds),
            'member_ids' => $createdMemberIds,
            'application_ids' => $createdApplicationIds,
            'login_password' => $password,
            'operator_email' => $operator?->email,
            'forms_used' => $forms->count(),
        ];
    }

    public function purge(string $tag, bool $delete = true): array
    {
        $tag = $this->normalizeTag($tag);

        $users = User::query()
            ->where('email', 'like', strtolower($tag).'.member%@dummy.local')
            ->orWhere('member_no', 'like', $tag.'-%')
            ->get();

        $userIds = $users->pluck('id');
        $applicationsQuery = AidApplication::query()->whereIn('user_id', $userIds);

        $summary = [
            'tag' => $tag,
            'members_found' => $users->count(),
            'applications_found' => (clone $applicationsQuery)->count(),
            'status_histories_found' => ApplicationStatusHistory::query()
                ->whereIn('aid_application_id', (clone $applicationsQuery)->pluck('id'))
                ->count(),
        ];

        if ($users->isEmpty() || ! $delete) {
            return $summary;
        }

        DB::transaction(function () use ($users): void {
            foreach ($users as $user) {
                $user->delete();
            }
        });

        return $summary;
    }

    public function normalizeTag(string $tag): string
    {
        $normalized = Str::of($tag)
            ->upper()
            ->replaceMatches('/[^A-Z0-9_-]+/', '-')
            ->trim('-')
            ->toString();

        if ($normalized === '') {
            throw new \InvalidArgumentException('Tag dummy tidak sah.');
        }

        return $normalized;
    }

    private function buildUserPayload(string $tag, int $index, string $password): array
    {
        $branches = ['HQ', 'Sandakan', 'Kota Kinabalu', 'Tawau', 'Lahad Datu'];
        $departments = ['Operasi', 'Kewangan', 'Sumber Manusia', 'Audit', 'Pentadbiran'];
        $states = ['Sabah', 'Wilayah Persekutuan Labuan'];
        $cities = ['Kota Kinabalu', 'Sandakan', 'Tawau', 'Lahad Datu', 'Labuan'];
        $jobTitles = ['Kerani', 'Penolong Pegawai', 'Pegawai Operasi', 'Eksekutif', 'Penyelia'];
        $genders = ['lelaki', 'perempuan'];
        $maritalStatuses = ['bujang', 'berkahwin', 'bercerai'];

        return [
            'name' => sprintf('Dummy Member %02d %s', $index, $tag),
            'email' => strtolower(sprintf('%s.member%03d@dummy.local', $tag, $index)),
            'password' => Hash::make($password),
            'role' => 'applicant',
            'member_no' => sprintf('%s-M%03d', $tag, $index),
            'phone' => sprintf('012%07d', 1000000 + $index),
            'nric' => $this->buildUniqueNric($tag, $index),
            'department' => $departments[($index - 1) % count($departments)],
            'employment_grade' => ['W29', 'W32', 'W41', 'W44', 'W48'][($index - 1) % 5],
            'job_title' => $jobTitles[($index - 1) % count($jobTitles)],
            'state' => $states[($index - 1) % count($states)],
            'branch' => $branches[($index - 1) % count($branches)],
            'address' => sprintf('Lot %d, Jalan Dummy %02d, Taman Ujian %s', 10 + $index, $index, $tag),
            'postcode' => str_pad((string) (88000 + ($index % 1000)), 5, '0', STR_PAD_LEFT),
            'city' => $cities[($index - 1) % count($cities)],
            'gender' => $genders[($index - 1) % count($genders)],
            'marital_status' => $maritalStatuses[($index - 1) % count($maritalStatuses)],
            'first_login_completed' => true,
            'email_verified_at' => now(),
        ];
    }

    private function resolveStatus(int $index): string
    {
        $statuses = [
            AidApplication::STATUS_SUBMITTED,
            AidApplication::STATUS_UNDER_REVIEW,
            AidApplication::STATUS_APPROVED,
            AidApplication::STATUS_REJECTED,
            AidApplication::STATUS_DRAFT,
            AidApplication::STATUS_KUIRI,
            AidApplication::STATUS_DISBURSED,
        ];

        return $statuses[($index - 1) % count($statuses)];
    }

    private function buildStatusTimestamps(string $status, int $index): array
    {
        $createdAt = now()->subDays($index + 2)->setTime(9, 0);
        $submittedAt = null;
        $reviewedAt = null;
        $decidedAt = null;
        $paidAt = null;
        $paymentPreparedAt = null;
        $paymentApprovedAt = null;
        $updatedAt = $createdAt->copy()->addHours(2);

        if ($status !== AidApplication::STATUS_DRAFT) {
            $submittedAt = $createdAt->copy()->addHours(1);
            $updatedAt = $submittedAt->copy();
        }

        if (in_array($status, [
            AidApplication::STATUS_UNDER_REVIEW,
            AidApplication::STATUS_APPROVED,
            AidApplication::STATUS_REJECTED,
            AidApplication::STATUS_KUIRI,
            AidApplication::STATUS_DISBURSED,
        ], true)) {
            $reviewedAt = $createdAt->copy()->addDay()->setTime(10, 30);
            $updatedAt = $reviewedAt->copy();
        }

        if (in_array($status, [
            AidApplication::STATUS_APPROVED,
            AidApplication::STATUS_REJECTED,
            AidApplication::STATUS_DISBURSED,
        ], true)) {
            $decidedAt = $createdAt->copy()->addDays(2)->setTime(11, 15);
            $updatedAt = $decidedAt->copy();
        }

        if ($status === AidApplication::STATUS_DISBURSED) {
            $paymentPreparedAt = $createdAt->copy()->addDays(3)->setTime(14, 0);
            $paymentApprovedAt = $createdAt->copy()->addDays(3)->setTime(15, 0);
            $paidAt = $createdAt->copy()->addDays(4)->setTime(16, 0);
            $updatedAt = $paidAt->copy();
        }

        return [
            'created_at' => $createdAt,
            'submitted_at' => $submittedAt,
            'reviewed_at' => $reviewedAt,
            'decided_at' => $decidedAt,
            'paid_at' => $paidAt,
            'payment_prepared_at' => $paymentPreparedAt,
            'payment_approved_at' => $paymentApprovedAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function buildApplicationContent(?FormSchema $form, int $index): array
    {
        $categories = ['kebajikan', 'kesihatan', 'pendidikan', 'perumahan', 'umum'];
        $category = $form?->category_key ?: $categories[($index - 1) % count($categories)];
        $amount = (float) (500 + (($index * 175) % 4500));
        $priorityScore = 25 + (($index * 11) % 70);

        $dynamicPayload = [
            'tujuan_permohonan' => 'Data dummy untuk ujian aliran permohonan di VPS.',
            'catatan_tambahan' => sprintf('Batch dummy %s item %d', $this->normalizeTag($form?->category_key ?: 'UMUM'), $index),
        ];
        $triageAnswers = [
            'akuan_benar' => true,
            'dokumen_lengkap' => $index % 3 !== 0,
        ];

        if ($form) {
            $dynamicPayload['_form_id'] = $form->id;

            $fields = collect(data_get($form->schema_json, 'fields', []))
                ->sortBy(fn ($field) => (int) ($field['order'] ?? 0))
                ->values();

            foreach ($fields as $fieldIndex => $field) {
                if (!is_array($field) || ($field['type'] ?? null) === 'instruction') {
                    continue;
                }

                $name = $this->normalizeFieldName($field, $fieldIndex + 1);
                $type = (string) ($field['type'] ?? 'text');
                $label = (string) ($field['label'] ?? $name);

                if ($type === 'checkbox') {
                    $triageAnswers[$name] = $index % 2 === 0;
                    continue;
                }

                if ($type === 'file') {
                    $dynamicPayload[$name] = [
                        'original_name' => Str::slug($label).'-dummy.pdf',
                    ];
                    continue;
                }

                $dynamicPayload[$name] = $this->fakeFieldValue($type, $label, $field, $index);
            }
        }

        return [
            'triage_answers' => $triageAnswers,
            'dynamic_payload' => $dynamicPayload,
            'category_tags' => [$category],
            'requested_amount' => $amount,
            'priority_score' => $priorityScore,
            'priority_label' => $priorityScore >= 75 ? 'Tinggi' : ($priorityScore >= 50 ? 'Sederhana' : 'Normal'),
            'priority_reason' => 'Rekod dummy untuk semakan UI, senarai, dan aliran operasi.',
        ];
    }

    private function normalizeFieldName(array $field, int $index): string
    {
        if (!empty($field['name'])) {
            return (string) $field['name'];
        }

        $baseLabel = (string) ($field['label'] ?? $field['type'] ?? 'field');

        return (string) Str::of($baseLabel)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s-]/', '')
            ->trim()
            ->replaceMatches('/\s+/', '_')
            ->replaceMatches('/_+/', '_')
            ->prepend((string) ($field['type'] ?? 'field')."_{$index}_");
    }

    private function fakeFieldValue(string $type, string $label, array $field, int $index): mixed
    {
        $options = collect($field['options'] ?? [])->filter()->values();

        return match ($type) {
            'number' => (string) (100 + $index),
            'currency' => (string) (500 + ($index * 25)),
            'date' => now()->subDays($index)->toDateString(),
            'textarea' => $label.' dummy untuk tujuan ujian sistem.',
            'select', 'radio' => $options->isNotEmpty()
                ? (string) $options[($index - 1) % $options->count()]
                : $label.' pilihan dummy',
            default => $label.' '.$index,
        };
    }

    private function buildUniqueNric(string $tag, int $index): string
    {
        $hash = hexdec(hash('crc32b', $tag.'-'.$index));
        $value = 100000000000 + ($hash % 89999999999);

        return str_pad((string) $value, 12, '0', STR_PAD_LEFT);
    }
}
