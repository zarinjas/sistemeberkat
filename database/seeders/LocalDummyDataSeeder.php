<?php

namespace Database\Seeders;

use App\Models\AidApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $users = $this->seedDummyUsers();

        $this->seedDummyApplications(
            $users['applicantA'],
            $users['applicantB'],
            $users['admin'],
            $users['superadmin'],
        );

        $this->call(InfoContentDummySeeder::class);
    }

    private function seedDummyUsers(): array
    {
        $applicantA = User::query()->firstOrCreate(
            ['email' => 'dummy.applicant1@berkat.local'],
            [
                'name' => 'Dummy Applicant One',
                'role' => 'applicant',
                'member_no' => 'DUMAPP001',
                'nric' => '900101101111',
                'phone' => '0123000001',
                'branch' => 'Kota Kinabalu',
                'first_login_completed' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $applicantB = User::query()->firstOrCreate(
            ['email' => 'dummy.applicant2@berkat.local'],
            [
                'name' => 'Dummy Applicant Two',
                'role' => 'applicant',
                'member_no' => 'DUMAPP002',
                'nric' => '910202101222',
                'phone' => '0123000002',
                'branch' => 'Sandakan',
                'first_login_completed' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'dummy.admin@berkat.local'],
            [
                'name' => 'Dummy Admin Local',
                'role' => 'admin',
                'member_no' => 'DUMADM001',
                'nric' => '820303101333',
                'phone' => '0123000003',
                'branch' => 'HQ',
                'first_login_completed' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $superadmin = User::query()->firstOrCreate(
            ['email' => 'dummy.superadmin@berkat.local'],
            [
                'name' => 'Dummy Superadmin Local',
                'role' => 'superadmin',
                'member_no' => 'DUMSUP001',
                'nric' => '810404101444',
                'phone' => '0123000004',
                'branch' => 'HQ',
                'first_login_completed' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        return [
            'applicantA' => $applicantA,
            'applicantB' => $applicantB,
            'admin' => $admin,
            'superadmin' => $superadmin,
        ];
    }

    private function seedDummyApplications(User $applicantA, User $applicantB, User $admin, User $superadmin): void
    {
        $rows = [
            [
                'reference_no' => 'DUMMY-APP-2026-0001',
                'user_id' => $applicantA->id,
                'status' => AidApplication::STATUS_SUBMITTED,
                'category_tags' => ['kesihatan'],
                'priority_score' => 52,
                'priority_label' => 'Normal',
                'priority_reason' => 'Permohonan bantuan rawatan asas',
                'requested_amount' => 1200.00,
                'submitted_at' => now()->subDays(6),
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'reference_no' => 'DUMMY-APP-2026-0002',
                'user_id' => $applicantA->id,
                'status' => AidApplication::STATUS_UNDER_REVIEW,
                'category_tags' => ['pendidikan'],
                'priority_score' => 74,
                'priority_label' => 'Tinggi',
                'priority_reason' => 'Yuran peperiksaan dan peralatan pembelajaran',
                'requested_amount' => 2500.00,
                'submitted_at' => now()->subDays(4),
                'reviewed_at' => now()->subDays(3),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(3),
            ],
            [
                'reference_no' => 'DUMMY-APP-2026-0003',
                'user_id' => $applicantB->id,
                'status' => AidApplication::STATUS_APPROVED,
                'category_tags' => ['kebajikan'],
                'priority_score' => 68,
                'priority_label' => 'Sederhana',
                'priority_reason' => 'Bantuan kebajikan keluarga',
                'requested_amount' => 1800.00,
                'paid_amount' => 1800.00,
                'transaction_ref' => 'TXN-DUMMY-0003',
                'submitted_at' => now()->subDays(10),
                'reviewed_at' => now()->subDays(9),
                'decided_at' => now()->subDays(8),
                'paid_at' => now()->subDays(7),
                'payment_prepared_by_user_id' => $admin->id,
                'payment_prepared_at' => now()->subDays(7),
                'payment_approved_by_user_id' => $superadmin->id,
                'payment_approved_at' => now()->subDays(7),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(7),
            ],
            [
                'reference_no' => 'DUMMY-APP-2026-0004',
                'user_id' => $applicantB->id,
                'status' => AidApplication::STATUS_REJECTED,
                'category_tags' => ['umum'],
                'priority_score' => 40,
                'priority_label' => 'Rendah',
                'priority_reason' => 'Maklumat sokongan tidak lengkap',
                'requested_amount' => 900.00,
                'submitted_at' => now()->subDays(12),
                'reviewed_at' => now()->subDays(11),
                'decided_at' => now()->subDays(10),
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(10),
            ],
            [
                'reference_no' => 'DUMMY-APP-2026-0005',
                'user_id' => $applicantA->id,
                'status' => AidApplication::STATUS_DRAFT,
                'category_tags' => ['perumahan'],
                'priority_score' => 15,
                'priority_label' => 'Draft',
                'priority_reason' => 'Belum dihantar',
                'requested_amount' => 3000.00,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subHours(8),
            ],
        ];

        foreach ($rows as $row) {
            AidApplication::query()->firstOrCreate(
                ['reference_no' => $row['reference_no']],
                [
                    'user_id' => $row['user_id'],
                    'status' => $row['status'],
                    'triage_answers' => ['dummy' => true],
                    'dynamic_payload' => [
                        'details' => $row['priority_reason'],
                        'dummy' => true,
                    ],
                    'category_tags' => $row['category_tags'],
                    'requested_amount' => $row['requested_amount'],
                    'paid_amount' => $row['paid_amount'] ?? null,
                    'transaction_ref' => $row['transaction_ref'] ?? null,
                    'payment_prepared_by_user_id' => $row['payment_prepared_by_user_id'] ?? null,
                    'payment_approved_by_user_id' => $row['payment_approved_by_user_id'] ?? null,
                    'priority_score' => $row['priority_score'],
                    'priority_label' => $row['priority_label'],
                    'priority_reason' => $row['priority_reason'],
                    'submitted_at' => $row['submitted_at'] ?? null,
                    'reviewed_at' => $row['reviewed_at'] ?? null,
                    'decided_at' => $row['decided_at'] ?? null,
                    'paid_at' => $row['paid_at'] ?? null,
                    'payment_prepared_at' => $row['payment_prepared_at'] ?? null,
                    'payment_approved_at' => $row['payment_approved_at'] ?? null,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]
            );
        }
    }
}
