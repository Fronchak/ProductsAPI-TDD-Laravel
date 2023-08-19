<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_should_save_user_and_return_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Bob',
            'email' => 'user@gmail.com',
            'password' => '123456',
            'confirm_password' => '123456'
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('users', 1);
        $user = User::all()->first();
        $this->assertNotEquals($user->password, '123456');
        $response->assertJson(fn(AssertableJson $json) =>
            $json->whereType('access_token', 'string')
                ->etc()
        );
    }

    public function test_register_should_return_unprocessable_when_email_is_not_valid()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Bob',
            'email' => 'usergmail.com',
            'password' => '123456',
            'confirm_password' => '123456'
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseEmpty('users');
        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('message')
                    ->has('errors')
                    ->etc()
        );
        $response->assertJsonPath('errors.email.0', 'The email must be a valid email address.');
    }

    public function test_register_should_return_unprocessable_when_name_is_empty() {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'user@gmail.com',
            'password' => '123456',
            'confirm_password' => '123456'
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseEmpty('users');
        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('message')
                    ->has('errors')
                    ->etc()
        );
        $response->assertJsonPath('errors.name.0', 'The name is required.');
    }

    public function test_register_should_return_unprocessable_when_password_length_is_lower_then_four(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Bob',
            'email' => 'user@gmail.com',
            'password' => '123',
            'confirm_password' => '123'
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseEmpty('users');
        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('message')
                    ->has('errors')
                    ->etc()
        );
        $response->assertJsonPath('errors.password.0', 'Password must have at least 4 characters.');
    }

    public function test_register_should_return_unprocessable_when_confirm_password_does_not_match(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Bob',
            'email' => 'user@gmail.com',
            'password' => '1234',
            'confirm_password' => '1235'
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseEmpty('users');
        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('message')
                    ->has('errors')
                    ->etc()
        );
        $response->assertJsonPath('errors.confirm_password.0', 'Passwords must match.');
    }

    public function test_register_should_return_unprocessable_when_email_already_exists_in_database(): void
    {
        User::factory()->create([
            'email' => 'user@gmail.com'
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Bob',
            'email' => 'user@gmail.com',
            'password' => '1234',
            'confirm_password' => '1234'
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('users', 1);
        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('message')
                    ->has('errors')
                    ->etc()
        );
        $response->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_login_should_return_unprocessable_when_email_is_empty(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'password' => '1234'
        ]);

        $response->assertUnprocessable();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('message')
                ->has('errors')
                ->etc()
        );
        $response->assertJsonPath('errors.email.0', 'The email is required.');
    }

    public function test_login_should_return_unprocessable_when_password_is_empty(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@gmail.com',
            'password' => ''
        ]);

        $response->assertUnprocessable();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('message')
                ->has('errors')
                ->etc()
        );
        $response->assertJsonPath('errors.password.0', 'The password is required.');
    }

    public function test_login_should_return_unhauthorized_when_password_is_wrong(): void
    {
        User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('123456')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@gmail.com',
            'password' => '12345'
        ]);

        $response->assertUnauthorized();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'Invalid email or password')
        );
    }

    public function test_login_should_return_unhauthorized_when_email_does_not_exists(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@gmail.com',
            'password' => '12345'
        ]);

        $response->assertUnauthorized();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'Invalid email or password')
        );
    }

    public function test_login_should_return_token_when_password_is_correct(): void
    {
        User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('123456')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@gmail.com',
            'password' => '123456'
        ]);

        $response->assertSuccessful();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('access_token', fn(string $token) => str($token)->isNotEmpty())
                ->etc()
        );
    }
}
