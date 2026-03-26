<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->upsertDemoUser([
            'email' => 'user@berkat.com',
            'name' => 'Applicant Demo',
            'role' => 'applicant',
            'member_no' => 'MBR001AA',
            'nric' => '901010101010',
            'branch' => 'Sandakan',
        ]);

        $this->upsertDemoUser([
            'email' => 'reviewer@berkat.com',
            'name' => 'Reviewer Demo',
            'role' => 'admin',
            'member_no' => 'ADM002AA',
            'nric' => '820202101010',
            'branch' => 'HQ',
        ]);

        $this->upsertDemoUser([
            'email' => 'superadmin@berkat.com',
            'name' => 'Superadmin IT Demo',
            'role' => 'superadmin',
            'member_no' => 'ADM001AA',
            'nric' => '800101101010',
            'branch' => 'HQ',
        ]);
    }

    private function upsertDemoUser(array $attributes): void
    {
        $user = User::query()
            ->where('email', $attributes['email'])
            ->orWhere('nric', $attributes['nric'])
            ->orWhere('member_no', $attributes['member_no'])
            ->first();

        if (!$user) {
            $user = new User();
        }

        $user->fill([
            'email' => $attributes['email'],
            'name' => $attributes['name'],
            'role' => $attributes['role'],
            'member_no' => $attributes['member_no'],
            'nric' => $attributes['nric'],
            'branch' => $attributes['branch'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $user->save();
    }
}
