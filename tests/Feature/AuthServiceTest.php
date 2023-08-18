<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Authservice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use function PHPUnit\Framework\assertNotEmpty;

class AuthServiceTest extends TestCase
{
    private Authservice $authService;
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
}
