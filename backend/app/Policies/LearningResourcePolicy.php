<?php
namespace App\Policies;
use App\Models\LearningResource;
use App\Models\User;
class LearningResourcePolicy {
 public function create(User $user): bool { return in_array($user->role,['faculty','admin'],true); }
 public function update(User $user, LearningResource $resource): bool { return $user->role === 'admin' || ($user->role === 'faculty' && $resource->uploaded_by_user_id === $user->id); }
 public function delete(User $user, LearningResource $resource): bool { return $this->update($user,$resource); }
}
