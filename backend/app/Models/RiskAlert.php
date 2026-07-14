<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAlert extends Model
{
    protected $fillable = [
        'student_id',
        'faculty_user_id',
        'risk_level',
        'risk_score',
        'prediction',
        'advice',
        'reasons',
        'source',
        'model',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'integer',
            'reasons' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function facultyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_user_id');
    }
}
