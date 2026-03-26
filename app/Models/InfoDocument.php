<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoDocument extends Model
{
    protected $fillable = [
        'title',
        'document_date',
        'category',
        'file_path',
        'is_active',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'is_active' => 'boolean',
    ];
}
