<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRecord extends Model
{
    protected $fillable = ['student_id', 'course_id', 'semester', 'year', 'quiz_marks', 'assignment_marks', 'mid_marks', 'final_marks', 'total_marks'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
