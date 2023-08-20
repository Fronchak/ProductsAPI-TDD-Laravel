<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\AuthTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductControllerTest extends TestCase
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
    public function test_show_should_return_not_found_when_id_does_not_exists(): void
    {
        $response = $this->getJson('/api/products/1');

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'Product not found')
        );
    }

    public function test_show_should_return_dto_successfully_when_id_exists(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/products/1');

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', $product->id)
                ->where('name', $product->name)
                ->where('description', $product->description)
                ->where('price', $product->price)
        );
    }

    //STORE TESTS
    public function test_store_should_return_unhauthorized_when_user_is_not_logged_in(): void
    {
        $response = $this->postJson('/api/products');

        $response->assertUnauthorized();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You must be authenticated to access this content')
        );
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_forbidden_when_user_is_not_admin_nor_worker(): void
    {
        $token = $this->getUserToken();

        $response = $this->withToken($token)->postJson('/api/products');

        $response->assertForbidden();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You do not have the required authorization')
        );
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_name_is_empty_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => '',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_name_is_empty_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => '',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_name_lenght_is_lower_than_3_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'AA',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name must have at least 3 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_name_lenght_is_lower_than_3_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'AA',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name must have at least 3 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_name_lenght_is_greater_than_150_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $longName = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => $longName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name cannot have more than 150 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_name_lenght_is_greater_than_150_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $longName = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => $longName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name cannot have more than 150 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_name_already_exists_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();
        $productName = "Book";
        Product::factory()->create([
            'name' => $productName
        ]);

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => $productName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is already been used.');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_store_should_return_unprocessable_when_name_already_exists_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();
        $productName = "Book";
        Product::factory()->create([
            'name' => $productName
        ]);

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => $productName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is already been used.');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_store_should_return_unprocessable_when_description_is_empty_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => '',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_description_is_empty_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => '',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_description_length_is_lower_than_10_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Very good',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description must have at least 10 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_description_length_is_lower_than_10_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Very good',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description must have at least 10 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_price_is_not_provided_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_price_is_not_provided_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_price_is_not_a_number_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 'price'
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be a valid number.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_price_is_not_a_number_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 'price'
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be a valid number.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_price_is_zero_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 0
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be positive.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_unprocessable_when_price_is_zero_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 0
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be positive.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_return_forbidden_when_data_is_valid_but_normal_user_id_logged_in(): void
    {
        $token = $this->getUserToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Book',
            'description' => 'Description',
            'price' => 1700
        ]);

        $response->assertForbidden();
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_should_save_product_and_return_dto_when_data_is_valid_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();
        $name = 'Computer';
        $description = 'Description';
        $price = 1500;

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => $name,
            'description' => $description,
            'price' => $price
        ]);

        $response->assertCreated();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 1)
                ->where('name', $name)
                ->where('description', $description)
                ->where('price', $price)
        );
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', compact('name', 'description', 'price'));
    }

    public function test_store_should_save_product_and_return_dto_when_data_is_valid_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();
        $name = 'Computer';
        $description = 'Description';
        $price = 1500;

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => $name,
            'description' => $description,
            'price' => $price
        ]);

        $response->assertCreated();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 1)
                ->where('name', $name)
                ->where('description', $description)
                ->where('price', $price)
        );
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', compact('name', 'description', 'price'));
    }

    //UPDATE TESTS
    public function test_update_should_return_unhauthorized_when_user_is_not_logged_in(): void
    {
        $response = $this->putJson('/api/products/1');

        $response->assertUnauthorized();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You must be authenticated to access this content')
        );
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_forbidden_when_user_is_not_admin_nor_worker(): void
    {
        $token = $this->getUserToken();

        $response = $this->withToken($token)->putJson('/api/products/1');

        $response->assertForbidden();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'You do not have the required authorization')
        );
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_name_is_empty_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => '',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_name_is_empty_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => '',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_name_lenght_is_lower_than_3_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'AA',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name must have at least 3 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_name_lenght_is_lower_than_3_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'AA',
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name must have at least 3 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_name_lenght_is_greater_than_150_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $longName = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => $longName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name cannot have more than 150 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_name_lenght_is_greater_than_150_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $longName = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => $longName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name cannot have more than 150 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_name_already_exists_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();
        $productName = "Book";
        Product::factory()->create([
            'name' => $productName
        ]);

        $response = $this->withToken($token)->putJson('/api/products/2', [
            'name' => $productName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is already been used.');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_update_should_return_unprocessable_when_name_already_exists_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();
        $productName = "Book";
        Product::factory()->create([
            'name' => $productName
        ]);

        $response = $this->withToken($token)->putJson('/api/products/2', [
            'name' => $productName,
            'description' => 'Description',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.name.0', 'The name is already been used.');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_update_should_return_unprocessable_when_description_is_empty_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => '',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_description_is_empty_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => '',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_description_length_is_lower_than_10_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => 'Very good',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description must have at least 10 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_description_length_is_lower_than_10_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => 'Very good',
            'price' => 500
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.description.0', 'The description must have at least 10 characters.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_price_is_not_provided_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_price_is_not_provided_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Computer',
            'description' => 'Description',
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price is required.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_price_is_not_a_number_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 'price'
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be a valid number.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_price_is_not_a_number_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 'price'
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be a valid number.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_price_is_zero_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 0
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be positive.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_unprocessable_when_price_is_zero_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 0
        ]);

        $response->assertUnprocessable();
        $this->assertIsAValidationResponse($response);
        $response->assertJsonPath('errors.price.0', 'The price must be positive.');
        $this->assertDatabaseEmpty('products');
    }

    public function test_update_should_return_not_found_when_all_data_is_valid_but_id_does_not_exists_and_user_is_logged_in(): void
    {
        Product::factory()->create();
        $token = $this->getWorkerToken();

        $response = $this->withToken($token)->putJson('/api/products/2', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 1200
        ]);

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'Product not found')
        );
        $this->assertDatabaseCount('products', 1);
    }

    public function test_update_should_return_not_found_when_all_data_is_valid_but_id_does_not_exists_and_admin_is_logged_in(): void
    {
        Product::factory()->create();
        $token = $this->getAdminToken();

        $response = $this->withToken($token)->putJson('/api/products/2', [
            'name' => 'Computer',
            'description' => 'Description',
            'price' => 1200
        ]);

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'Product not found')
        );
        $this->assertDatabaseCount('products', 1);
    }

    public function test_update_should_return_forbidden_when_data_is_valid_but_normal_user_id_logged_in(): void
    {
        Product::factory()->create();
        $token = $this->getUserToken();

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => 'Book',
            'description' => 'Description',
            'price' => 1700
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_update_should_update_product_and_return_dto_when_data_is_valid_and_worker_is_logged_in(): void
    {
        Product::factory(2)->create();
        $token = $this->getWorkerToken();
        $id = 2;
        $name = 'Computer';
        $description = 'Description';
        $price = 1500;

        $response = $this->withToken($token)->putJson('/api/products/2', [
            'name' => $name,
            'description' => $description,
            'price' => $price
        ]);

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 2)
                ->where('name', $name)
                ->where('description', $description)
                ->where('price', $price)
        );
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', compact('id', 'name', 'description', 'price'));
    }

    public function test_update_should_update_product_and_return_dto_when_data_is_valid_and_admin_is_logged_in(): void
    {
        Product::factory(2)->create();
        $token = $this->getAdminToken();
        $id = 2;
        $name = 'Computer';
        $description = 'Description';
        $price = 1500;

        $response = $this->withToken($token)->putJson('/api/products/2', [
            'name' => $name,
            'description' => $description,
            'price' => $price
        ]);

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 2)
                ->where('name', $name)
                ->where('description', $description)
                ->where('price', $price)
        );
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', compact('id', 'name', 'description', 'price'));
    }

    public function test_update_should_update_product_and_return_dto_when_name_alreary_exists_but_is_the_model_been_updated_and_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();
        $id = 1;
        $name = 'Computer';
        $description = 'Description';
        $price = 1500;
        Product::factory()->create([
            'name' => $name
        ]);

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => $name,
            'description' => $description,
            'price' => $price
        ]);

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 1)
                ->where('name', $name)
                ->where('description', $description)
                ->where('price', $price)
        );
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', compact('id', 'name', 'description', 'price'));
    }

    public function test_update_should_update_product_and_return_dto_when_name_alreary_exists_but_is_the_model_been_updated_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();
        $id = 1;
        $name = 'Computer';
        $description = 'Description';
        $price = 1500;
        Product::factory()->create([
            'name' => $name
        ]);

        $response = $this->withToken($token)->putJson('/api/products/1', [
            'name' => $name,
            'description' => $description,
            'price' => $price
        ]);

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('id', 1)
                ->where('name', $name)
                ->where('description', $description)
                ->where('price', $price)
        );
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', compact('id', 'name', 'description', 'price'));
    }

    //DESTROY TESTS
    public function test_destroy_should_return_unhauthorized_when_user_is_not_logged_in(): void
    {
        Product::factory()->create();
        $response = $this->deleteJson('/api/products/1');

        $response->assertUnauthorized();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_destroy_should_return_forbidden_when_a_normal_user_is_logged_in(): void
    {
        $token = $this->getUserToken();
        Product::factory()->create();
        $response = $this->withToken($token)->deleteJson('/api/products/1');

        $response->assertForbidden();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_destroy_should_return_forbidden_when_worker_is_logged_in(): void
    {
        $token = $this->getWorkerToken();
        Product::factory()->create();
        $response = $this->withToken($token)->deleteJson('/api/products/1');

        $response->assertForbidden();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_destroy_should_return_not_found_when_admin_is_logged_in_but_id_does_not_exists(): void
    {
        $token = $this->getAdminToken();
        Product::factory()->create();
        $response = $this->withToken($token)->deleteJson('/api/products/2');

        $response->assertNotFound();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('message', 'Product not found')
        );
        $this->assertDatabaseCount('products', 1);
    }

    public function test_destroy_should_remove_product_from_database_when_id_exits_and_admin_is_logged_in(): void
    {
        $token = $this->getAdminToken();
        Product::factory(2)->create();
        $response = $this->withToken($token)->deleteJson('/api/products/2');

        $response->assertNoContent();
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseMissing('products', ['id' => 2]);
    }

    //INDEX TESTS
    public function test_index_should_use_default_pagination_values_when_no_params_specified(): void
    {
        Product::factory(10)->create();
        $response = $this->getJson('/api/products');

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('total', 10)
                ->where('per_page', 4)
                ->where('current_page', 1)
                ->where('last_page', 3)
                ->whereType('data', 'array')
                ->etc()
        );
    }

    public function test_index_should_use_param_values(): void
    {
        Product::factory(30)->create();
        $response = $this->getJson('/api/products?size=5&page=4');

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('total', 30)
                ->where('per_page', 5)
                ->where('current_page', 4)
                ->where('last_page', 6)
                ->whereType('data', 'array')
                ->etc()
        );
    }

    public function test_index_filter_query_is_been_used_to_filter_result_pagination(): void
    {
        Product::factory()->create(['name' => 'Harry Potter 1', 'description' => 'Description']);
        Product::factory()->create(['name' => 'Senhor dos anéis 1', 'description' => 'Description']);
        Product::factory()->create(['name' => 'Harry Potter 2', 'description' => 'Description']);
        Product::factory()->create(['name' => 'Senhor dos anéis 2', 'description' => 'Description']);
        $product5 = Product::factory()->create(['name' => 'Harry Potter 3', 'description' => 'Description']);
        Product::factory()->create(['name' => 'Senhor dos anéis 3', 'description' => 'Description']);
        $product7 = Product::factory()->create(['name' => 'Interestelar', 'description' => 'Description Potter ...']);
        Product::factory()->create(['name' => 'Mad max', 'description' => 'Description']);
        Product::factory()->create(['name' => 'Harry Potter 4', 'description' => 'Description']);

        $response = $this->getJson('/api/products?size=2&page=2&filter=Potter');

        $response->assertSuccessful();
        $response->assertJson(fn(AssertableJson $json) =>
            $json->where('total', 5)
                ->where('per_page', 2)
                ->where('current_page', 2)
                ->where('last_page', 3)
                ->whereType('data', 'array')
                ->etc()
        );
        $response->assertJsonPath('data.0.id', $product5->id);
        $response->assertJsonPath('data.1.id', $product7->id);
    }
}
