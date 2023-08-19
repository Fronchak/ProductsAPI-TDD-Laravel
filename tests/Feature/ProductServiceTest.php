<?php

namespace Tests\Feature;

use App\Exceptions\EntityNotFoundException;
use App\Interfaces\ProductMapperInterface;
use App\Mappers\ProductMapper;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;
    private ProductService $productService;

    private int $id1 = 10;
    private string $name1 = 'Computer';
    private string $description1 = 'Description 1';
    private float $price1 = 3000.00;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ProductMapperInterface::class, function(MockInterface $mock) {
            $mock->shouldReceive('mapToDTO')->andReturn([
                'id' => $this->id1,
                'name' => $this->name1,
                'description' => $this->description1,
                'price' => $this->price1
            ]);
        });

        $this->productService = app(ProductService::class);
    }
    /**
     * A basic feature test example.
     */
    public function test_show_should_throw_entity_not_found_when_id_does_nos_exists(): void
    {
        Product::factory()->create();

        $this->assertThrows(function () {
            $this->productService->show(2);
        }, EntityNotFoundException::class);
    }

    public function test_show_should_return_dto_when_id_exists(): void
    {
        Product::factory()->create();

        $result = $this->productService->show(1);

        $this->assertIsProductDTO($result);
    }

    private function assertIsProductDTO($result): void
    {
        $this->assertEquals($this->id1, $result['id']);
        $this->assertEquals($this->name1, $result['name']);
        $this->assertEquals($this->description1, $result['description']);
        $this->assertEquals($this->price1, $result['price']);
    }

    public function test_store_should_save_product_and_return_dto(): void
    {
        $data = [
            'name' => 'TV',
            'description' => 'Description',
            'price' => 1500
        ];

        $result = $this->productService->store($data);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['name' => 'TV']);
        $this->assertIsProductDTO($result);
    }

    public function test_update_should_throw_entity_not_found_when_id_does_not_exists(): void
    {
        Product::factory()->create();

        $this->assertThrows(function () {
            $this->productService->update([], 2);
        }, EntityNotFoundException::class);
    }

    public function test_update_should_update_database_and_return_dto_when_id_exists(): void
    {
        Product::factory()->create();
        $data = [
            'name' => 'TV',
            'description' => 'Description',
            'price' => 1500
        ];

        $result = $this->productService->update($data, 1);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['name' => 'TV']);
        $this->assertIsProductDTO($result);
    }

    public function test_destroy_should_throw_entity_not_found_when_id_does_not_exits(): void
    {
        $this->assertThrows(function() {
            $this->productService->destroy(1);
        }, EntityNotFoundException::class);
    }

    public function test_destroy_remove_model_from_database_when_id_exists(): void
    {
        Product::factory(2)->create();

        $this->productService->destroy(2);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseMissing('products', ['id' => 2]);
    }

    public function test_index_should_return_products_pagination(): void
    {
        $size = 4;
        $page = 2;
        $total = 10;
        Product::factory($total)->create();


        $result = $this->productService->index('', $size, $page);

        $this->assertEquals($total, $result->total());
        $this->assertEquals($page, $result->currentPage());
        $this->assertEquals($size, $result->perPage());
        $this->assertEquals(3, $result->lastPage());
    }

    public function test_index_should_return_products_pagination_filtered_by_name_and_description(): void
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

        $size = 2;
        $page = 2;

        $result = $this->productService->index('Potter', $size, $page);

        $this->assertEquals(5, $result->total());
        $this->assertEquals($size, $result->currentPage());
        $this->assertEquals($size, $result->perPage());
        $items = $result->items();
        $this->assertEquals($product5->id, $items[0]->id);
        $this->assertEquals($product7->id, $items[1]->id);
    }
}
