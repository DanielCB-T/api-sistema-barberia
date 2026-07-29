<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/products  (público, paginado)
     * Filtros opcionales: ?in_stock=1&per_page=10&page=2
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);

        $products = Product::query()
            ->when($request->boolean('in_stock'), fn ($q) => $q->where('stock', '>', 0))
            ->orderBy('name')
            ->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * GET /api/products/{product}  (público)
     */
    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    /**
     * POST /api/products  (solo admin)
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product))
            ->additional(['message' => 'Producto creado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/products/{product}  (solo admin)
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return (new ProductResource($product))
            ->additional(['message' => 'Producto actualizado correctamente.']);
    }

    /**
     * DELETE /api/products/{product}  (solo admin)
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
