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

    public function test_store_should_return_unprocessable_when_price_is_not_provided_than_10_and_worker_is_logged_in(): void
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

    public function test_store_should_return_unprocessable_when_price_is_not_provided_than_10_and_admin_is_logged_in(): void
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
}
