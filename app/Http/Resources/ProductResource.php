<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'base_price' => (float) $this->base_price,
            'thumbnail_url' => $this->thumbnailUrl(),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'role' => $this->creator->role,
            ] : null),
            'listings_count' => $this->whenCounted('listings'),
            // Only present on the shop owner's catalog: is this product already in the selected shop?
            'is_listed' => $this->whenHas('is_listed', fn () => (bool) $this->is_listed),
            'can_edit' => (bool) $request->user()?->can('update', $this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
