<?php

namespace Tests\Feature;

use App\Models\User;
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

    private static int $ADMIN_ID = 1;
    private static int $WORKER_ID = 2;
    private static int $NORMAL_USER_ID = 3;

    private static int $ADMIN_ROLE_ID = 1;
    private static int $WORKER_ROLE_ID = 2;

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

    public function test_index_should_return_unhauthorized_when_user_is_not_logged_in(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You must be authenticated to access this content')
        );
    }

    public function test_index_should_return_forbidden_when_user_is_logged_in(): void
    {
        $token = $this->getUserToken();
        $response = $this->withToken($token)->getJson('/api/users');

        $response->assertForbidden();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You do not have the required authorization')
        );
    }

    public function test_index_should_return_all_users_when_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->getJson('/api/users');

        $response->assertSuccessful();
        $response->assertJsonPath('0.id', 1);
        $response->assertJsonPath('0.email', 'admin@gmail.com');
        $response->assertJsonPath('1.id', 2);
        $response->assertJsonPath('1.email', 'worker@gmail.com');
        $response->assertJsonMissingPath('0.password');
        $response->assertJsonMissingPath('1.password');
    }

    public function test_update_roles_should_return_unhauthorized_when_user_is_not_logged_in(): void
    {
        $response = $this->putJson('/api/users/1/roles', [
            'roles' => [1, 2]
        ]);

        $response->assertUnauthorized();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You must be authenticated to access this content')
        );

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_forbidden_when_user_is_logged_in(): void
    {
        $token = $this->getUserToken();
        $response = $this->withToken($token)->putJson('/api/users/1/roles', [
            'roles' => [1, 2]
        ]);

        $response->assertForbidden();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You do not have the required authorization')
        );

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_unprocessable_when_worker_is_logged_in_but_roles_is_not_provided(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->putJson('/api/users/1/roles');

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.roles.0', 'The roles are required.');

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_unprocessable_when_admin_is_logged_in_but_roles_is_not_provided(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->putJson('/api/users/1/roles');

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.roles.0', 'The roles are required.');

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_unprocessable_when_worker_is_logged_in_but_roles_is_not_an_array(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->putJson('/api/users/1/roles', [
            'roles' => 'admin'
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.roles.0', 'The roles must be an array.');

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_unprocessable_when_admin_is_logged_in_but_roles_is_not_an_array(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->putJson('/api/users/1/roles', [
            'roles' => 'admin'
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.roles.0', 'The roles must be an array.');

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_not_found_when_worker_is_logged_in_but_id_does_not_exists(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->putJson('/api/users/10/roles', [
            'roles' => [1, 2]
        ]);

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'User not found')
        );
    }

    public function test_update_roles_should_return_not_found_when_admin_is_logged_in_but_id_does_not_exists(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->putJson('/api/users/10/roles', [
            'roles' => [1, 2]
        ]);

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'User not found')
        );
    }

    public function test_update_roles_should_return_forbidden_when_user_try_to_update_its_own_roles(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->putJson('/api/users/' . UserControllerTest::$ADMIN_ID . '/roles', [
            'roles' => [1, 2]
        ]);

        $response->assertForbidden();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You cannot update your own roles.')
        );

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_forbidden_when_a_worker_try_to_update_the_roles_of_a_admin(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->putJson('/api/users/' . UserControllerTest::$ADMIN_ID . '/roles', [
            'roles' => [1, 2]
        ]);

        $response->assertForbidden();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', "You don't have permission to update the roles of a admin.")
        );

        $admin = User::find(UserControllerTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_return_forbidden_when_a_worker_try_to_give_admin_to_another_user(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->putJson('/api/users/' . UserControllerTest::$NORMAL_USER_ID . '/roles', [
            'roles' => [UserControllerTest::$ADMIN_ROLE_ID, UserControllerTest::$WORKER_ROLE_ID]
        ]);

        $response->assertForbidden();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', "You don't have permission to give admin to others users.")
        );

        $normalUser = User::find(UserControllerTest::$NORMAL_USER_ID);
        $this->assertEquals(0, $normalUser->roles->count());
        $this->assertFalse($normalUser->hasRole('admin'));
        $this->assertFalse($normalUser->hasRole('worker'));
    }

    public function test_update_roles_user_should_receive_worker_role(): void
    {
        $token = $this->getWorkerToken();
        $response = $this->withToken($token)->putJson('/api/users/' . UserControllerTest::$NORMAL_USER_ID . '/roles', [
            'roles' => [UserControllerTest::$WORKER_ROLE_ID]
        ]);

        $response->assertNoContent();
        $normalUser = User::find(UserControllerTest::$NORMAL_USER_ID);
        $this->assertEquals(1, $normalUser->roles->count());
        $this->assertFalse($normalUser->hasRole('admin'));
        $this->assertTrue($normalUser->hasRole('worker'));
    }

    public function test_update_roles_worker_should_recive_admin_role(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->putJson('/api/users/' . UserControllerTest::$WORKER_ID . '/roles', [
            'roles' => [UserControllerTest::$ADMIN_ROLE_ID, UserControllerTest::$WORKER_ROLE_ID]
        ]);

        $response->assertNoContent();
        $worker = User::find(UserControllerTest::$WORKER_ID);
        $this->assertEquals(2, $worker->roles->count());
        $this->assertTrue($worker->hasRole('admin'));
        $this->assertTrue($worker->hasRole('worker'));
    }

    public function test_update_roles_worker_should_lose_all_roles(): void
    {
        $token = $this->getAdminToken();
        $response = $this->withToken($token)->putJson('/api/users/' . UserControllerTest::$WORKER_ID . '/roles', [
            'roles' => [0]
        ]);

        $response->assertNoContent();
        $worker = User::find(UserControllerTest::$WORKER_ID);
        $this->assertEquals(0, $worker->roles->count());
        $this->assertFalse($worker->hasRole('admin'));
        $this->assertFalse($worker->hasRole('worker'));
    }

    public function test_update_roles_one_admin_can_update_another_admin_roles(): void
    {
        $otherAdmin = User::create([
            'name' => 'Other Admin',
            'email' => 'otheradmin@gmail.com',
            'password' => bcrypt('admin')
        ]);
        $otherAdmin->assignRole('admin');

        $token = $this->getAdminToken();
        $response = $this->withToken($token)->putJson('/api/users/' . $otherAdmin->id . '/roles', [
            'roles' => [UserControllerTest::$WORKER_ROLE_ID]
        ]);

        $response->assertNoContent();
        $otherAdmin = User::find($otherAdmin->id);
        $this->assertEquals(1, $otherAdmin->roles->count());
        $this->assertFalse($otherAdmin->hasRole('admin'));
        $this->assertTrue($otherAdmin->hasRole('worker'));
    }
}
