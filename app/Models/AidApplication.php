<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class AidApplication extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_KUIRI = 'kuiri';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DISBURSED = 'disbursed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'reference_no',
        'status',
        'triage_answers',
        'dynamic_payload',
        'category_tags',
        'requested_amount',
        'paid_amount',
        'transaction_ref',
        'payment_prepared_by_user_id',
        'payment_approved_by_user_id',
        'priority_score',
        'priority_label',
        'priority_reason',
        'submitted_at',
        'reviewed_at',
        'decided_at',
        'paid_at',
        'payment_prepared_at',
        'payment_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'triage_answers' => 'array',
            'dynamic_payload' => 'array',
            'category_tags' => 'array',
            'requested_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'decided_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_prepared_at' => 'datetime',
            'payment_approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentPreparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_prepared_by_user_id');
    }

    public function paymentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_approved_by_user_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->latest('changed_at');
    }

    public function walletDocuments(): BelongsToMany
    {
        return $this->belongsToMany(WalletDocument::class, 'application_wallet_documents')
            ->withPivot('relation_type')
            ->withTimestamps();
    }
}
