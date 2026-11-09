<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product in the shared catalog.
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'name',
        'sku',
        'description',
        'base_price',
        'thumbnail',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            // Delete listings through Eloquent so their thumbnails are cleaned up.
            $product->listings()->get()->each->delete();
        });

        static::deleted(function (Product $product) {
            Media::delete($product->thumbnail);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_products')
            ->withPivot(['id', 'price', 'title', 'description', 'thumbnail', 'is_active'])
            ->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', function (Builder $q) use ($term) {
            $q->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        });
    }

    public function thumbnailUrl(): ?string
    {
        return Media::url($this->thumbnail);
    }
}
