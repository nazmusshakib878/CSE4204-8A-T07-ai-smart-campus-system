<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Recommendation extends Model {
 protected $fillable=['title','description','recommendation_type','target_user_id','course_id','created_by_user_id','target_user','score'];
 protected function casts(): array { return ['score'=>'float']; }
 public function targetUser(): BelongsTo { return $this->belongsTo(User::class,'target_user_id'); }
 public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by_user_id'); }
}
