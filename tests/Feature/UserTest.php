<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LanguageEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\CreatesApplication;
use Tests\TestCase;

class UserTest extends TestCase
{
    use CreatesApplication;
    use DatabaseMigrations;
    use DatabaseTransactions;
    use WithFaker;

    /**
     * Create user from endpoint.
     * @test
     * @covers UserController::store
     * */
    public function create_user(): void
    {
        $user = [
            'email' => $this->faker()->email(),
            'password' => $this->faker()->password(),
            'name' => $this->faker()->name(),
            'birthdate' => $this->faker()->date(),
            'language' => LanguageEnum::DANISH->value,
        ];
        $response = $this->post('/api/user', $user);

        unset($user['password']);
        $response->assertStatus(201)->assertJson($user);
    }

    /**
     * Log in the user from endpoint.
     * @test
     * @covers UserController::store
     * */
    public function login_user_success(): void
    {
        /** @var User */
        $user = User::factory([
            'password' => '123456',
        ])->create();
        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => '123456',
        ]);

        $response->assertStatus(200)->assertJson($user->toArray());
    }

    /**
     * Log in the user from endpoint.
     * @test
     * @covers LoginController::authenticate
     * */
    public function login_user_failed(): void
    {
        /** @var User */
        $user = User::factory([
            'password' => '123456',
        ])->create();
        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => '1234',
        ]);

        $response->assertStatus(401);
    }
}
