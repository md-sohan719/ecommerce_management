<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * The products a shop owner has listed in one of their shops, with the
 * shop-specific price, details and thumbnail.
 */
class ListingController extends Controller
{
    private const FIELDS = ['price', 'title', 'description', 'is_active'];

    public function index(Request $request, Shop $shop): AnonymousResourceCollection
    {
        Gate::authorize('manage', $shop);

        $listings = $shop->listings()
            ->with('product.creator')
            ->search($request->query('search'))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'hidden', fn ($q) => $q->where('is_active', false))
            ->latest('id')
            ->paginate($this->perPage($request));

        return ListingResource::collection($listings);
    }

    public function store(ListingRequest $request, Shop $shop): JsonResponse
    {
        $data = $request->validated();

        $listing = new ShopProduct(Arr::only($data, self::FIELDS));
        $listing->shop_id = $shop->id;
        $listing->product_id = $data['product_id'];

        if ($request->hasFile('thumbnail')) {
            $listing->thumbnail = Media::store($request->file('thumbnail'), 'listings');
        }

        $listing->save();

        return ListingResource::make($listing->load('product.creator'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ListingRequest $request, Shop $shop, ShopProduct $listing): ListingResource
    {
        $listing->fill(Arr::only($request->validated(), self::FIELDS));

        if ($request->hasFile('thumbnail')) {
            $listing->thumbnail = Media::replace($listing->thumbnail, $request->file('thumbnail'), 'listings');
        } elseif ($request->boolean('remove_thumbnail') && $listing->thumbnail) {
            // Fall back to the catalog thumbnail.
            Media::delete($listing->thumbnail);
            $listing->thumbnail = null;
        }

        $listing->save();

        return ListingResource::make($listing->load('product.creator'));
    }

    public function destroy(Shop $shop, ShopProduct $listing): Response
    {
        Gate::authorize('manage', $shop);

        // Only removes the product from this shop; the catalog product stays.
        $listing->delete();

        return response()->noContent();
    }
}
