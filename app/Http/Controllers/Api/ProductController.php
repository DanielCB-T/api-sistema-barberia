<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ImageStorage;
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
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = ImageStorage::store($request->file('image'), 'products');
        }

        $product = Product::create($data);

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
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $oldImage = $product->image;
            $data['image'] = ImageStorage::store($request->file('image'), 'products');
            ImageStorage::delete($oldImage);
        }

        $product->update($data);

        return (new ProductResource($product))
            ->additional(['message' => 'Producto actualizado correctamente.']);
    }

    /**
     * DELETE /api/products/{product}  (solo admin)
     */
    public function destroy(Product $product)
    {
        ImageStorage::delete($product->image);
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
