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

    public function roleChangeAudits(): HasMany
    {
        return $this->hasMany(RoleChangeAudit::class, 'user_id');
    }

    public function roleChangesInitiated(): HasMany
    {
        return $this->hasMany(RoleChangeAudit::class, 'changed_by_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isApplicant(): bool
    {
        return $this->role === 'applicant';
    }
}
