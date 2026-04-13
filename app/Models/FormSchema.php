<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSchema extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'category_key',
        'category_name',
        'card_color',
        'version',
        'schema_json',
        'published_at',
        'published_by_user_id',
        'is_active',
        'lifecycle_status',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
