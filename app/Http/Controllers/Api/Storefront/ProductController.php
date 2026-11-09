<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\StorefrontProductResource;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public (customer-facing) product endpoints.
 *
 * Every query starts from shop listings (shop_products) and is limited to
 * visible listings (active listing in an active shop). A catalog product that
 * is not assigned to any shop therefore can never be returned here.
 */
class ProductController extends Controller
{
    /**
     * Products across all shops.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ShopProduct::query()->visible();

        return StorefrontProductResource::collection(
            $this->filter($query, $request)->paginate($this->perPage($request, 12, 48))
        );
    }

    /**
     * Products of one shop.
     */
    public function shopProducts(Request $request, Shop $shop): AnonymousResourceCollection
    {
        abort_unless($shop->is_active, 404);

        $query = $shop->listings()->visible();

        return StorefrontProductResource::collection(
            $this->filter($query, $request)->paginate($this->perPage($request, 12, 48))
        );
    }

    /**
     * A product as sold by one shop, plus the other shops that also sell it.
     */
    public function show(Shop $shop, int $productId): JsonResponse
    {
        abort_unless($shop->is_active, 404);

        $listing = $shop->listings()
            ->visible()
            ->where('product_id', $productId)
            ->with(['product', 'shop'])
            ->firstOrFail();

        $otherShops = ShopProduct::query()
            ->visible()
            ->where('product_id', $productId)
            ->where('shop_id', '!=', $shop->id)
            ->with(['product', 'shop'])
            ->orderBy('price')
            ->get();

        return response()->json([
            'data' => StorefrontProductResource::make($listing),
            'other_shops' => StorefrontProductResource::collection($otherShops),
        ]);
    }

    private function filter(Builder|HasMany $query, Request $request): Builder|HasMany
    {
        return $query->with(['product', 'shop'])
            ->search($request->query('search'))
            ->sortBy($request->query('sort'));
    }
}
