<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    public function test_user_can_get_own_categories(): void
    {
        $user = $this->actingAsUser();
        Category::factory()->count(3)->for($user)->create();
        Category::factory()->count(2)->create(); // чужі категорії

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_user_can_create_category(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/categories', [
            'name' => 'Work',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Work']);

        $this->assertDatabaseHas('categories', ['name' => 'Work']);
    }

    public function test_user_cannot_create_category_without_name(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/categories', []);

        $response->assertStatus(422);
    }

    public function test_user_can_update_own_category(): void
    {
        $user = $this->actingAsUser();
        $category = Category::factory()->for($user)->create();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_user_cannot_update_others_category(): void
    {
        $this->actingAsUser();
        $otherCategory = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$otherCategory->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_category(): void
    {
        $user = $this->actingAsUser();
        $category = Category::factory()->for($user)->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_delete_others_category(): void
    {
        $this->actingAsUser();
        $otherCategory = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$otherCategory->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401);
    }
}
