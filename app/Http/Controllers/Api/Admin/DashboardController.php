<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'shops' => Shop::count(),
            'active_shops' => Shop::active()->count(),
            'products' => Product::count(),
            'unlisted_products' => Product::doesntHave('listings')->count(),
            'shop_owners' => User::shopOwners()->count(),
            'listings' => ShopProduct::count(),
        ]);
    }
}
