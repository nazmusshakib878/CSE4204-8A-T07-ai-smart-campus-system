<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    protected $table = 'academic_records';

    protected $fillable = [
        'student_id',
        'course_id',
        'course_code_snapshot',
        'course_title_snapshot',
        'credit_hours_snapshot',
        'grade',
        'grade_point',
        'semester',
        'semester_number',
        'year',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'credit_hours_snapshot' => 'float',
            'grade_point' => 'float',
            'semester_number' => 'integer',
        ];
    }

    public function scopeWithoutDemo($query)
    {
        return $query->where(fn ($records) => $records->whereNull('source')->orWhere('source', '!=', 'demo'));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
