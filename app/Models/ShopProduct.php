<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A listing: a catalog product assigned to a shop, with a shop-specific price
 * and optional overrides for the title, details and thumbnail.
 */
class ShopProduct extends Model
{
    use HasFactory;

    protected $table = 'shop_products';

    protected $fillable = [
        'shop_id',
        'product_id',
        'price',
        'title',
        'description',
        'thumbnail',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (ShopProduct $listing) {
            Media::delete($listing->thumbnail);
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Listings customers are allowed to see: the listing itself is active and
     * it belongs to an active shop.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('shop_products.is_active', true)
            ->whereHas('shop', fn (Builder $q) => $q->where('is_active', true));
    }

    /**
     * Search on the effective (shop-specific or catalog) name and details.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', function (Builder $q) use ($term) {
            $q->where(function (Builder $q) use ($term) {
                $q->where('shop_products.title', 'like', "%{$term}%")
                    ->orWhere('shop_products.description', 'like', "%{$term}%")
                    ->orWhereHas('product', fn (Builder $p) => $p->search($term));
            });
        });
    }

    /**
     * Storefront sort options: latest (default), price_asc, price_desc.
     */
    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('shop_products.price')->orderBy('shop_products.id'),
            'price_desc' => $query->orderByDesc('shop_products.price')->orderBy('shop_products.id'),
            default => $query->latest('shop_products.id'),
        };
    }

    public function effectiveName(): string
    {
        return $this->title ?: $this->product->name;
    }

    public function effectiveDescription(): ?string
    {
        return $this->description ?: $this->product->description;
    }

    public function effectiveThumbnailUrl(): ?string
    {
        return Media::url($this->thumbnail) ?? $this->product->thumbnailUrl();
    }
}
