<?php

namespace App\Http\Resources;

use App\Models\ShopProduct;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A shop listing as seen by the shop owner: raw overrides plus the effective
 * values customers will see.
 *
 * @mixin ShopProduct
 */
class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'product_id' => $this->product_id,
            'price' => (float) $this->price,
            'is_active' => $this->is_active,
            // Shop-specific overrides (null = use the catalog value)
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_url' => Media::url($this->thumbnail),
            // What customers see
            'effective' => [
                'name' => $this->effectiveName(),
                'description' => $this->effectiveDescription(),
                'thumbnail_url' => $this->effectiveThumbnailUrl(),
            ],
            'product' => ProductResource::make($this->whenLoaded('product')),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
