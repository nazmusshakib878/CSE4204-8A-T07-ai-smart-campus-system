<?php
namespace App\Policies;
use App\Models\Recommendation;
use App\Models\User;
class RecommendationPolicy {
 public function view(User $user, Recommendation $recommendation): bool { return $user->role === 'admin' || $recommendation->target_user_id === $user->id; }
 public function create(User $user): bool { return in_array($user->role,['faculty','admin'],true); }
 public function update(User $user, Recommendation $recommendation): bool { return $user->role === 'admin' || ($user->role === 'faculty' && $recommendation->created_by_user_id === $user->id); }
 public function delete(User $user, Recommendation $recommendation): bool { return $this->update($user,$recommendation); }
}
