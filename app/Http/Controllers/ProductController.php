<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->middleware('jwt.auth')->only(['store']);
        $this->middleware('role:worker|admin')->only(['store']);
        $this->productService = $productService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductStoreRequest $request)
    {
        $responseData = $this->productService->store($request->all(['name', 'description', 'price']));
        return response($responseData, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $responseData = $this->productService->show($id);
        return response($responseData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
