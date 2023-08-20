<?php

namespace Tests\Feature;

use Database\Seeders\AuthTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = true;
    protected $seeder = AuthTestSeeder::class;

    //UTIL METHODS
    private function getUserToken(): string
    {
        return auth()->attempt([
            'email' => 'user@gmail.com',
            'password' => 'user'
        ]);
    }

    private function getWorkerToken(): string
    {
        return auth()->attempt([
            'email' => 'worker@gmail.com',
            'password' => 'worker'
        ]);
    }

    private function getAdminToken(): string
    {
        return auth()->attempt([
            'email' => 'admin@gmail.com',
            'password' => 'admin'
        ]);
    }

    private function assertIsAValidationResponse(TestResponse $response): void
    {
        $response->assertJson(fn (AssertableJson $json) =>
        $json->has('message')
            ->has('errors')
            ->etc()
        );
    }

    //SHOW TESTS
    public function test_show_should_return_unhauthorized_when_user_is_not_logged_in(): void
    {
        $response = $this->getJson('/api/users/10');

        $response->assertUnauthorized();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You must be authenticated to access this content')
        );
    }

    public function test_show_should_return_forbidden_when_user_is_logged_in(): void
    {
        $token = $this->getUserToken();
        $response = $this->withToken($token)->getJson('/api/users/10');

        $response->assertForbidden();
    }

    public function test_show_should_return_not_found_when_worker_is_logged_in_but_id_does_not_exists(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->getJson('/api/users/10');

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'User not found')
        );
    }

    public function test_show_should_return_not_found_when_admin_is_logged_in_but_id_does_not_exists(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->getJson('/api/users/10');

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'User not found')
        );
    }

    public function test_show_should_return_dto_successfully_when_worker_is_logged_in_and_id_exists(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->getJson('/api/users/1');

        $response->assertSuccessful();
        $response->assertJsonMissingPath('password');
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 1)
                ->where('name', 'Admin')
                ->where('email', 'admin@gmail.com')
                ->whereType('roles', 'array')
        );
    }

    public function test_show_should_return_dto_successfully_when_admin_is_logged_in_and_id_exists(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->getJson('/api/users/1');

        $response->assertSuccessful();
        $response->assertJsonMissingPath('password');
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 1)
                ->where('name', 'Admin')
                ->where('email', 'admin@gmail.com')
                ->whereType('roles', 'array')
        );
    }
}
