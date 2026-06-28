<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'title',
        'description',
        'recommendation_type',
        'target_user',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }
}
