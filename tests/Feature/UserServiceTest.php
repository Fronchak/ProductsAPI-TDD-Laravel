<?php

namespace Tests\Feature;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnprocessableException;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\AuthTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = true;
    protected $seeder = AuthTestSeeder::class;

    private static int $ADMIN_ID = 1;
    private static int $WORKER_ID = 2;
    private static int $NORMAL_USER_ID = 3;

    private static int $ADMIN_ROLE_ID = 1;
    private static int $WORKER_ROLE_ID = 2;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    public function test_show_should_throw_entity_not_found_when_id_does_not_exists(): void
    {
        $this->assertThrows(function() {
            $this->userService->show(10);
        }, EntityNotFoundException::class);
    }

    public function test_show_should_return_dto_when_id_exists(): void
    {
        $name = 'Other admin';
        $email = 'admin2@gmail.com';
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('admin')
        ]);
        $user->assignRole('admin');
        $user->assignRole('worker');

        $result = $this->userService->show($user->id);

        $this->assertEquals($user->id, $result['id']);
        $this->assertEquals($user->name, $result['name']);
        $this->assertEquals($user->email, $result['email']);
        $this->assertArrayNotHasKey('password', $result);
        $roles = $result['roles'];
        $this->assertTrue($roles->contains('admin'));
        $this->assertTrue($roles->contains('worker'));
        $this->assertEquals(2, $roles->count());
    }

    public function test_index_show_return_all_users(): void
    {
        $result = $this->userService->index();

        $admin = $result[0];
        $this->assertEquals(1, $admin['id']);
        $this->assertEquals('admin@gmail.com', $admin['email']);
        $this->assertEquals('Admin', $admin['name']);

        $worker = $result[1];
        $this->assertEquals(2, $worker['id']);
        $this->assertEquals('worker@gmail.com', $worker['email']);
        $this->assertEquals('Worker', $worker['name']);

        $this->assertEquals(3, count($result));
    }

    public function test_update_roles_should_throw_entity_not_found_when_id_does_not_exists(): void
    {
        $admin = User::find(UserServiceTest::$ADMIN_ID);
        auth()->setUser($admin);

        $this->assertThrows(function() {
            $this->userService->updateRoles(10, []);
        }, EntityNotFoundException::class);
    }

    public function test_update_roles_should_throw_forbidden_exception_when_user_try_to_update_its_own_roles(): void
    {
        $admin = User::find(UserServiceTest::$ADMIN_ID);
        auth()->setUser($admin);

        $this->assertThrows(function() {
            $this->userService->updateRoles(UserServiceTest::$ADMIN_ID, []);
        }, ForbiddenException::class);

        $admin = User::find(UserServiceTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_throw_forbidden_exception_when_a_worker_try_to_update_the_roles_of_a_admin(): void
    {
        $worker = User::find(UserServiceTest::$WORKER_ID);
        auth()->setUser($worker);

        $this->assertThrows(function() {
            $this->userService->updateRoles(UserServiceTest::$ADMIN_ID, []);
        }, ForbiddenException::class);

        $admin = User::find(UserServiceTest::$ADMIN_ID);
        $this->assertEquals(1, $admin->roles->count());
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_update_roles_should_throw_forbidden_exception_when_a_worker_try_to_give_admin_to_another_user(): void
    {
        $worker = User::find(UserServiceTest::$WORKER_ID);
        auth()->setUser($worker);

        $this->assertThrows(function() {
            $this->userService->updateRoles(UserServiceTest::$NORMAL_USER_ID, [UserServiceTest::$ADMIN_ROLE_ID]);
        }, ForbiddenException::class);

        $normalUser = User::find(UserServiceTest::$NORMAL_USER_ID);
        $this->assertEquals(0, $normalUser->roles->count());
    }

    public function test_update_roles_user_should_recive_worker_role(): void
    {
        $worker = User::find(UserServiceTest::$WORKER_ID);
        auth()->setUser($worker);

        $this->userService->updateRoles(UserServiceTest::$NORMAL_USER_ID, [UserServiceTest::$WORKER_ROLE_ID]);

        $normalUser = User::find(UserServiceTest::$NORMAL_USER_ID);
        $this->assertEquals(1, $normalUser->roles->count());
        $this->assertTrue($normalUser->hasRole('worker'));
        $this->assertFalse($normalUser->hasRole('admin'));
    }

    public function test_update_roles_worker_should_recive_admin_role(): void
    {
        $admin = User::find(UserServiceTest::$ADMIN_ID);
        auth()->setUser($admin);

        $this->userService->updateRoles(UserServiceTest::$WORKER_ID, [UserServiceTest::$WORKER_ROLE_ID, UserServiceTest::$ADMIN_ROLE_ID]);

        $worker = User::find(UserServiceTest::$WORKER_ID);
        $this->assertEquals(2, $worker->roles->count());
        $this->assertTrue($worker->hasRole('worker'));
        $this->assertTrue($worker->hasRole('admin'));
    }

    public function test_update_roles_worker_should_lose_all_roles(): void
    {
        $admin = User::find(UserServiceTest::$ADMIN_ID);
        auth()->setUser($admin);

        $this->userService->updateRoles(UserServiceTest::$WORKER_ID, []);

        $worker = User::find(UserServiceTest::$WORKER_ID);
        $this->assertEquals(0, $worker->roles->count());
        $this->assertFalse($worker->hasRole('worker'));
        $this->assertFalse($worker->hasRole('admin'));
    }

    public function test_update_roles_one_admin_can_update_another_admin_roles(): void
    {
        $otherAdmin = User::create([
            'name' => 'Other Admin',
            'email' => 'otheradmin@gmail.com',
            'password' => bcrypt('admin')
        ]);
        $otherAdmin->assignRole('admin');

        $admin = User::find(UserServiceTest::$ADMIN_ID);
        auth()->setUser($admin);

        $this->userService->updateRoles($otherAdmin->id, [UserServiceTest::$WORKER_ROLE_ID]);

        $otherAdmin = User::find($otherAdmin->id);
        $this->assertEquals(1, $otherAdmin->roles->count());
        $this->assertTrue($otherAdmin->hasRole('worker'));
        $this->assertFalse($otherAdmin->hasRole('admin'));
    }
}
