<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Notice extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'publish_date',
        'category',
        'audience',
        'target_department',
        'target_role',
        'target_semester',
        'recipient_name',
        'recipient_reference',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    protected $appends = [
        'attachment_url',
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

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }
}