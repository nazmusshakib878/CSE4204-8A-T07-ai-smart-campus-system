<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'publish_date',
        'category',
        'audience',
        'recipient_name',
        'recipient_reference',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
