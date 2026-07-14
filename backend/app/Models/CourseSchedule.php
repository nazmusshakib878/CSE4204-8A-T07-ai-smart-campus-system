<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CourseSchedule extends Model {
 protected $fillable=['course_id','day_of_week','starts_at','ends_at','room','class_type'];
 public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
