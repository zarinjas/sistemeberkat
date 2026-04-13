<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationBlast extends Model
{
    protected $fillable = [
        'sent_by_user_id',
        'target_type',
        'target_meta',
        'subject',
        'message',
        'image_path',
        'channels',
        'recipient_count',
        'recipient_user_ids',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'target_meta' => 'array',
            'channels' => 'array',
            'recipient_user_ids' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
