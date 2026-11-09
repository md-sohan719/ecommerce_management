<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory()->admin(),
            'name' => ucfirst(fake()->words(3, true)),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'description' => fake()->paragraph(),
            'base_price' => fake()->randomFloat(2, 50, 5000),
            'thumbnail' => null,
        ];
    }
}
