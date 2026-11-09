<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public (customer-facing) shop endpoints. Inactive shops are never exposed.
 */
class ShopController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $shops = Shop::active()
            ->withCount(['listings as products_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return ShopResource::collection($shops);
    }

    public function show(Shop $shop): ShopResource
    {
        abort_unless($shop->is_active, 404);

        $shop->loadCount(['listings as products_count' => fn ($q) => $q->where('is_active', true)]);

        return ShopResource::make($shop);
    }
}
