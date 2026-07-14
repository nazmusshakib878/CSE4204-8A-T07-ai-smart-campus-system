<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LearningResource extends Model {
 protected $table='learning_resources';
 protected $fillable=['title','description','category','resource_type','resource_url','course_id','uploaded_by_user_id','uploaded_by'];
 public function uploader(): BelongsTo { return $this->belongsTo(User::class,'uploaded_by_user_id'); }
 public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
