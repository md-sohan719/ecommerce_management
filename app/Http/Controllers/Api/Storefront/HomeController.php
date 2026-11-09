<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Http\Resources\StorefrontProductResource;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Everything the home page needs in one request, so all sections render together:
 *  - featured: the 3 shops with the most visible products, each with its highest-priced product (banner)
 *  - shops:    all active shops
 *  - products: the first page of visible products (same format as GET /storefront/products)
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $shops = Shop::active()
            ->withCount(['listings as products_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $featured = $shops
            ->where('products_count', '>', 0)
            ->sortByDesc('products_count')
            ->take(3)
            ->values()
            ->map(fn (Shop $shop) => [
                'shop' => ShopResource::make($shop),
                'product' => StorefrontProductResource::make(
                    ShopProduct::visible()
                        ->where('shop_id', $shop->id)
                        ->with('product')
                        ->sortBy('price_desc')
                        ->first()
                ),
            ]);

        $products = ShopProduct::visible()
            ->with(['product', 'shop'])
            ->search($request->query('search'))
            ->sortBy($request->query('sort'))
            ->paginate($this->perPage($request, 12, 48));

        return response()->json([
            'featured' => $featured,
            'shops' => ShopResource::collection($shops),
            'products' => StorefrontProductResource::collection($products)->response()->getData(true),
        ]);
    }
}
