<?php

namespace App\Mappers;

use App\Interfaces\ProductMapperInterface;
use App\Models\Product;

class ProductMapper implements ProductMapperInterface
{
    public function mapToDTO(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price
        ];
    }
}

?>
