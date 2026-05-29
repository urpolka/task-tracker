<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_belongs_to_user(): void
    {
        $task = Task::factory()->create();

        $this->assertInstanceOf(User::class, $task->user);
    }

    public function test_task_belongs_to_category(): void
    {
        $task = Task::factory()->create();

        $this->assertInstanceOf(Category::class, $task->category);
    }

    public function test_user_can_have_many_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->for($user)->create();

        $this->assertCount(3, $user->tasks);
    }

    public function test_task_has_correct_default_status(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $this->assertEquals('pending', $task->status);
    }
}
