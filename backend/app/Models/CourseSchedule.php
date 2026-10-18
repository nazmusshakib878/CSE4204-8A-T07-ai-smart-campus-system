<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CourseSchedule extends Model {
 protected $fillable=['course_id','semester','year','section','shift','day_of_week','starts_at','ends_at','room','instructor_initials','class_type','source_reference'];
 public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
