<?php

namespace App\Services;

use App\Exceptions\EntityNotFoundException;
use App\Interfaces\ProductMapperInterface;
use App\Models\Product;

class ProductService
{
    private ProductMapperInterface $productMapper;

    public function __construct(ProductMapperInterface $productMapper)
    {
        $this->productMapper = $productMapper;
    }

    public function show($id)
    {
        $product = $this->getProductById($id);
        return $this->productMapper->mapToDTO($product);
    }

    private function getProductById($id): Product
    {
        $product = Product::find($id);
        if($product === null) {
            throw new EntityNotFoundException('Product not found');
        }
        return $product;
    }

    public function store(array $data)
    {
        $product = new Product($data);
        $product->save();
        return $this->productMapper->mapToDTO($product);
    }

    public function update(array $data, $id)
    {
        $product = $this->getProductById($id);
        $product->fill($data);
        $product->update();
        return $this->productMapper->mapToDTO($product);
    }

    public function destroy($id)
    {
        $product = $this->getProductById($id);
        $product->delete();
    }

    public function index($filter, $size, $page)
    {
        $pagination = Product::select(['id', 'name', 'price'])
                ->where('name', 'like', '%' . $filter . '%')
                ->orWhere('description', 'like', '%' . $filter . '%')
                ->paginate($size, ['*'], 'page', $page);

        return $pagination;
    }
}

?>
