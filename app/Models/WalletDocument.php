<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'file_path',
        'mime_type',
        'file_size',
        'is_active',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(AidApplication::class, 'application_wallet_documents')
            ->withPivot('relation_type')
            ->withTimestamps();
    }
}
