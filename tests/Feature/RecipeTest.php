<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\CreatesApplication;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use CreatesApplication, DatabaseMigrations, DatabaseTransactions, WithFaker;
    private User|null $user = null;

    /**
     * Before each test, create user and login
     */
    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        /** @var User */
        $loggedIn = Auth::loginUsingId($user->id);
        $this->user = $loggedIn;
    }

    /**
     * Log out before next test
    */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Auth::logout();
    }
    /**
     * A basic feature test example.
     * @test
     * @covers RecipeController::store
     */
    public function create_recipe_with_login(): void
    {
        Category::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);
        $recipe = [
            "name" => $this->faker()->name(),
            "description" => $this->faker()->realText(),
            "note" => $this->faker()->realText(),
            "public" => $this->faker()->boolean(),
            "categories" => [1, 2, 3],
            "ingredients" => ["Ingredient 1", "Ingredient 2", "Ingredient 3", "Ingredient 4"],
            "tags" => ["tag 1", "tag 2"],
        ];
        $response = $this->post('/api/recipe', $recipe);
        $response->assertStatus(201);

        $this->assertEquals($response->json()['name'], $recipe['name']);
        $this->assertEquals($response->json()['description'], $recipe['description']);
        $this->assertCount(3, $response->json()['categories']);
        $this->assertCount(4, $response->json()['ingredients']);
        $this->assertCount(2, $response->json()['tags']);
    }

    /**
     * A basic feature test example.
     * @test
     * @covers RecipeController::store
     */
    public function create_recipe_without_login(): void
    {
        Auth::logout();
        $recipe = [
            "name" => $this->faker()->email(),
            "description" => $this->faker()->realText(),
            "note" => $this->faker()->realText(),
            "date" => $this->faker()->date(),
            "public" => $this->faker()->boolean(),
            "categories" => [1, 2, 3],
            "ingredients" => ["Ingredient 1", "Ingredient 2"],
            "tags" => ["tag 1", "tag 2"],
        ];
        $response = $this->post('/api/recipe', $recipe);

        $response->assertStatus(401);
    }
}
