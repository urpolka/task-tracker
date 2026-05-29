<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_belongs_to_user(): void
    {
        $category = Category::factory()->create();

        $this->assertInstanceOf(User::class, $category->user);
    }

    public function test_user_can_have_many_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->for($user)->create();

        $this->assertCount(3, $user->categories);
    }
}
