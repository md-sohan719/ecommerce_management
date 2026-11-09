<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with('creator')
            ->withCount('listings')
            ->search($request->query('search'))
            ->when($request->query('created_by') === 'admin', fn ($q) => $q->whereHas('creator', fn ($q) => $q->where('role', User::ROLE_ADMIN)))
            ->when($request->query('created_by') === 'shop_owner', fn ($q) => $q->whereHas('creator', fn ($q) => $q->where('role', User::ROLE_SHOP_OWNER)))
            ->when($request->query('status') === 'listed', fn ($q) => $q->has('listings'))
            ->when($request->query('status') === 'unlisted', fn ($q) => $q->doesntHave('listings'))
            ->latest('id')
            ->paginate($this->perPage($request));

        return ProductResource::collection($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated(), $request->user(), $request->file('thumbnail'));

        return ProductResource::make($product->load('creator')->loadCount('listings'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load('creator')->loadCount('listings'));
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $product = $this->products->update(
            $product,
            $request->validated(),
            $request->file('thumbnail'),
            $request->boolean('remove_thumbnail'),
        );

        return ProductResource::make($product->load('creator')->loadCount('listings'));
    }

    public function destroy(Product $product): Response
    {
        // Removing a catalog product also removes it from every shop (listings cascade).
        $product->delete();

        return response()->noContent();
    }
}
