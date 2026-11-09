<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ShopProduct;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Shop owners can add their own products to the shared catalog. Products they
 * create are immediately available to every other shop owner.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($request, $data) {
            $product = $this->products->create($data, $request->user(), $request->file('thumbnail'));

            // Optionally list it in one of the owner's shops straight away.
            if (! empty($data['shop_id'])) {
                ShopProduct::create([
                    'shop_id' => $data['shop_id'],
                    'product_id' => $product->id,
                    'price' => $data['price'] ?? $product->base_price,
                    'is_active' => true,
                ]);
            }

            return $product;
        });

        return ProductResource::make($product->load('creator')->loadCount('listings'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        Gate::authorize('update', $product);

        $product = $this->products->update(
            $product,
            $request->validated(),
            $request->file('thumbnail'),
            $request->boolean('remove_thumbnail'),
        );

        return ProductResource::make($product->load('creator')->loadCount('listings'));
    }

    public function destroy(Request $request, Product $product): Response
    {
        Gate::authorize('delete', $product);

        // Don't pull a product out from under other shops that are selling it.
        $listedElsewhere = $product->listings()
            ->whereHas('shop', fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('owner_id')
                ->orWhere('owner_id', '!=', $request->user()->id)))
            ->exists();

        if ($listedElsewhere) {
            throw ValidationException::withMessages([
                'product' => 'This product is listed by other shops, so it cannot be deleted. Remove it from your own shops instead.',
            ]);
        }

        $product->delete();

        return response()->noContent();
    }
}
