<?php
namespace App\Policies;
use App\Models\CampusTask;
use App\Models\User;
class CampusTaskPolicy {
 public function view(User $user, CampusTask $task): bool { return $user->role === 'admin' || $task->assigned_to_user_id === $user->id; }
 public function update(User $user, CampusTask $task): bool { return $this->view($user,$task); }
 public function delete(User $user, CampusTask $task): bool { return $this->view($user,$task); }
}
