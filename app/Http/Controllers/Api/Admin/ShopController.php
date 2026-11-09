<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ShopController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search'));

        $shops = Shop::query()
            ->with('owner')
            ->withCount('listings')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->latest('id')
            ->paginate($this->perPage($request));

        return ShopResource::collection($shops);
    }

    public function store(ShopRequest $request): JsonResponse
    {
        $shop = Shop::create($request->validated());

        return ShopResource::make($shop->load('owner')->loadCount('listings'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Shop $shop): ShopResource
    {
        return ShopResource::make($shop->load('owner')->loadCount('listings'));
    }

    public function update(ShopRequest $request, Shop $shop): ShopResource
    {
        $shop->update($request->validated());

        return ShopResource::make($shop->load('owner')->loadCount('listings'));
    }

    public function destroy(Shop $shop): Response
    {
        $shop->delete();

        return response()->noContent();
    }
}
