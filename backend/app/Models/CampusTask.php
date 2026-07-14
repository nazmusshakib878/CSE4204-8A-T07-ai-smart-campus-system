<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CampusTask extends Model {
 protected $table='tasks';
 protected $fillable=['title','description','assigned_to','assigned_to_user_id','due_date','status','priority'];
 protected function casts(): array { return ['due_date'=>'date:Y-m-d']; }
 public function assignee(): BelongsTo { return $this->belongsTo(User::class,'assigned_to_user_id'); }
}
