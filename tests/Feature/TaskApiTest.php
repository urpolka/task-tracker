<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    public function test_user_can_get_own_tasks(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->count(3)->for($user)->create();
        Task::factory()->count(2)->create(); // чужі задачі

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_user_can_create_task(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'My Task',
            'priority' => 'high',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'My Task']);
    }

    public function test_user_cannot_create_task_without_title(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'priority' => 'high',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_create_task_with_own_category(): void
    {
        $user = $this->actingAsUser();
        $category = Category::factory()->for($user)->create();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Task with category',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['category_id' => $category->id]);
    }

    public function test_user_cannot_create_task_with_others_category(): void
    {
        $this->actingAsUser();
        $otherCategory = Category::factory()->create();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Task with stolen category',
            'category_id' => $otherCategory->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->for($user)->create();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task',
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Updated Task',
                'status' => 'in_progress',
            ]);
    }

    public function test_user_cannot_update_others_task(): void
    {
        $this->actingAsUser();
        $otherTask = Task::factory()->create();

        $response = $this->putJson("/api/tasks/{$otherTask->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->for($user)->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_others_task(): void
    {
        $this->actingAsUser();
        $otherTask = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$otherTask->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_filter_tasks_by_status(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->for($user)->create(['status' => 'pending']);
        Task::factory()->for($user)->create(['status' => 'pending']);
        Task::factory()->for($user)->create(['status' => 'completed']);

        $response = $this->getJson('/api/tasks?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_user_can_filter_tasks_by_priority(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->for($user)->create(['priority' => 'high']);
        Task::factory()->for($user)->create(['priority' => 'high']);
        Task::factory()->for($user)->create(['priority' => 'low']);

        $response = $this->getJson('/api/tasks?priority=high');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_user_can_filter_tasks_by_category(): void
    {
        $user = $this->actingAsUser();
        $category = Category::factory()->for($user)->create();
        Task::factory()->for($user)->create(['category_id' => $category->id]);
        Task::factory()->for($user)->create(['category_id' => $category->id]);
        Task::factory()->for($user)->create();

        $response = $this->getJson("/api/tasks?category_id={$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }
}
