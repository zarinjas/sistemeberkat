<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'member_no',
    'phone',
    'nric',
    'department',
    'employment_grade',
    'job_title',
    'state',
    'profile_photo_path',
    'first_login_completed',
    'branch',
    'address',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function walletDocuments(): HasMany
    {
        return $this->hasMany(WalletDocument::class);
    }

    public function aidApplications(): HasMany
    {
        return $this->hasMany(AidApplication::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        if ($this->role === 'superadmin') {
            return true;
        }

        $superAdminEmails = array_filter(array_map(
            static fn ($email) => strtolower(trim((string) $email)),
            str_getcsv((string) env('SUPERADMIN_EMAILS', 'superadmin@berkat.com')),
        ));

        return in_array(strtolower((string) $this->email), $superAdminEmails, true);
    }

    public function isApplicant(): bool
    {
        return $this->role === 'applicant';
    }
}
