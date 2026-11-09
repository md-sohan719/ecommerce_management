<?php

namespace App\Http\Resources;

use App\Models\ShopProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A product as customers see it in a specific shop: shop-specific price and
 * the shop's overrides for name, details and thumbnail (falling back to the
 * catalog values).
 *
 * @mixin ShopProduct
 */
class StorefrontProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'listing_id' => $this->id,
            'id' => $this->product_id,
            'name' => $this->effectiveName(),
            'description' => $this->effectiveDescription(),
            'thumbnail_url' => $this->effectiveThumbnailUrl(),
            'price' => (float) $this->price,
            'sku' => $this->product->sku,
            'shop' => $this->whenLoaded('shop', fn () => [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
                'slug' => $this->shop->slug,
            ]),
        ];
    }
}
