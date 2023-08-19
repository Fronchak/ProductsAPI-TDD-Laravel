<?php

namespace Tests\Feature;

use App\Exceptions\UnhauthorizationException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    public function test_register_should_save_user_and_return_token(): void
    {
        $result = $this->authService->register([
            'email' => 'user@gmail.com',
            'name' => 'Ana',
            'password' => 'password'
        ]);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertNotEmpty($result['access_token']);
        $this->assertDatabaseCount('users', 1);
        $user = User::all()->first();
        $this->assertNotEquals($user->password, 'password');
    }

    public function test_login_should_return_token_if_password_is_correct(): void
    {
        User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('123456')
        ]);

        $result = $this->authService->login('user@gmail.com', '123456');

        $this->assertArrayHasKey('access_token', $result);
        $this->assertNotEmpty($result['access_token']);
    }

    public function test_login_should_throw_unhauthorization_exception_when_password_is_incorrect(): void
    {
        User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('123456')
        ]);

        $this->assertThrows(function() {
            $this->authService->login('user@gmail.com', '1234567');
        }, UnhauthorizationException::class);
    }

    public function test_login_should_throw_unhauthorization_exception_when_email_does_not_exists(): void
    {
        User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('123456')
        ]);

        $this->assertThrows(function() {
            $this->authService->login('ana@gmail.com', '123456');
        }, UnhauthorizationException::class);
    }
}
