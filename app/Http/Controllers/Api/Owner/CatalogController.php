<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    /**
     * The shared product catalog: every product created by the admin or any
     * shop owner. When ?shop_id= is given (one of the owner's shops), each
     * product is flagged with whether it is already listed in that shop.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $shopId = $request->filled('shop_id')
            ? $user->shops()->whereKey($request->integer('shop_id'))->value('id')
            : null;

        $products = Product::query()
            ->with('creator')
            ->withCount('listings')
            ->search($request->query('search'))
            ->when($request->boolean('mine'), fn ($q) => $q->where('created_by', $user->id))
            ->when($shopId, function ($q) use ($shopId, $request) {
                $q->withExists(['listings as is_listed' => fn ($q) => $q->where('shop_id', $shopId)]);

                if ($request->query('availability') === 'not_listed') {
                    $q->whereDoesntHave('listings', fn ($q) => $q->where('shop_id', $shopId));
                }
            })
            ->latest('id')
            ->paginate($this->perPage($request, 12));

        return ProductResource::collection($products);
    }
}
