<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->middleware('jwt.auth')->only(['store', 'update', 'destroy']);
        $this->middleware('role:worker|admin')->only(['store', 'update']);
        $this->middleware('role:admin')->only('destroy');
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
    public function update(ProductUpdateRequest $request, $id)
    {
        $responseData = $this->productService->update($request->all(['name', 'description', 'price']), $id);
        return response($responseData);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->productService->destroy($id);
        return response('', 204);
    }
}
