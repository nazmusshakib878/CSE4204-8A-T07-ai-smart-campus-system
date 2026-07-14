<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceMetric extends Model
{
    protected $table = 'performance_metrics';

    protected $fillable = [
        'student_id',
                'semester',
        'year',
        'cgpa',
        'semester_gpa',
        'completed_credits',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
