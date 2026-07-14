<?php

namespace Tests\Feature;

use App\Models\CampusTask;
use App\Models\LearningResource;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_only_see_and_modify_their_own_tasks(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $own = CampusTask::create(['title' => 'Mine', 'assigned_to_user_id' => $owner->id]);
        $foreign = CampusTask::create(['title' => 'Private', 'assigned_to_user_id' => $other->id]);

        Sanctum::actingAs($owner);
        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->deleteJson('/api/tasks/'.$foreign->id)->assertForbidden();
    }

    public function test_students_cannot_write_learning_resources_and_faculty_cannot_delete_anothers(): void
    {
        $student = User::factory()->create();
        $faculty = User::factory()->create(['role' => 'faculty']);
        $otherFaculty = User::factory()->create(['role' => 'faculty']);
        $resource = LearningResource::create([
            'title' => 'Notes', 'category' => 'Study', 'resource_type' => 'link',
            'resource_url' => 'https://example.test', 'uploaded_by_user_id' => $otherFaculty->id,
        ]);

        Sanctum::actingAs($student);
        $this->postJson('/api/learning-resources', [
            'title' => 'Bad', 'category' => 'Study', 'resource_type' => 'link',
            'resource_url' => 'https://example.test',
        ])->assertForbidden();

        Sanctum::actingAs($faculty);
        $this->deleteJson('/api/learning-resources/'.$resource->id)->assertForbidden();
    }

    public function test_recommendations_are_target_scoped_and_students_cannot_create_them(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        Recommendation::create([
            'title' => 'Own', 'recommendation_type' => 'course', 'target_user_id' => $student->id,
        ]);
        Recommendation::create([
            'title' => 'Other', 'recommendation_type' => 'course', 'target_user_id' => $other->id,
        ]);

        Sanctum::actingAs($student);
        $this->getJson('/api/recommendations')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Own');
        $this->postJson('/api/recommendations', [
            'title' => 'No', 'recommendation_type' => 'course', 'target_user_id' => $student->id,
        ])->assertForbidden();
    }
}
