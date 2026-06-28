<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampusTask extends Model
{
    protected $table = 'tasks';

    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'due_date',
        'status',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
        ];
    }
}
