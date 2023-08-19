<?php

namespace Tests\Unit;

use App\Mappers\ProductMapper;
use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductMapperTest extends TestCase
{
    private ProductMapper $productMapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productMapper = new ProductMapper();
    }

    public function test_map_to_dto_should_convert_model_correctly(): void
    {
        $id = 10;
        $name = 'Computer';
        $description = 'Gamer computer';
        $price = 2000;

        $product  = new Product([
            'name' => $name,
            'price' => $price,
            'description' => $description
        ]);
        $product->id = $id;

        $result = $this->productMapper->mapToDTO($product);

        $this->assertEquals($id, $result['id']);
        $this->assertEquals($name, $result['name']);
        $this->assertEquals($description, $result['description']);
        $this->assertEquals($price, $result['price']);
    }
}
