<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'shops' => $this->whenLoaded('shops', fn () => $this->shops->map(fn ($shop) => [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->slug,
                'is_active' => $shop->is_active,
            ])),
            'shops_count' => $this->whenCounted('shops'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
