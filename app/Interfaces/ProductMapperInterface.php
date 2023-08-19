<?php

namespace App\Interfaces;

use App\Models\Product;

interface ProductMapperInterface
{
    public function mapToDTO(Product $product): array;
}

?>
