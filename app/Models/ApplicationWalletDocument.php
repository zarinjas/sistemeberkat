<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ApplicationWalletDocument extends Model
{
    protected $fillable = [
        'aid_application_id',
        'wallet_document_id',
        'relation_type',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AidApplication::class, 'aid_application_id');
    }

    public function walletDocument(): BelongsTo
    {
        return $this->belongsTo(WalletDocument::class);
    }
}
