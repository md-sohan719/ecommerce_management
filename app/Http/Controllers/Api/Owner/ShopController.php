<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopController extends Controller
{
    /**
     * The shops owned by the authenticated shop owner.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $shops = $request->user()->shops()
            ->withCount('listings')
            ->orderBy('name')
            ->get();

        return ShopResource::collection($shops);
    }
}
