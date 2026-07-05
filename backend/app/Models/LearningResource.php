<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningResource extends Model
{
    protected $table = 'learning_resources';

    protected $fillable = [
        'title',
        'description',
        'category',
        'resource_type',
        'resource_url',
        'uploaded_by',
    ];
}
