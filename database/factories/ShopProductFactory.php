<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProduct>
 */
class ShopProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'product_id' => Product::factory(),
            'price' => fake()->randomFloat(2, 50, 5000),
            'title' => null,
            'description' => null,
            'thumbnail' => null,
            'is_active' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
