<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardPoster extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'aspect_ratio',
        'is_active',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
