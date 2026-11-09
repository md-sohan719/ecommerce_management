<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_not_assigned_to_any_shop_are_not_visible(): void
    {
        $shop = Shop::factory()->create();
        $listed = Product::factory()->create(['name' => 'Listed product']);
        Product::factory()->create(['name' => 'Unassigned product']);
        ShopProduct::factory()->create(['shop_id' => $shop->id, 'product_id' => $listed->id]);

        $this->getJson('/api/storefront/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Listed product')
            ->assertDontSee('Unassigned product');

        $this->getJson('/api/storefront/products?search=Unassigned')->assertJsonCount(0, 'data');
    }

    public function test_hidden_listings_and_inactive_shops_are_not_visible(): void
    {
        $active = Shop::factory()->create();
        $inactive = Shop::factory()->inactive()->create();

        ShopProduct::factory()->create(['shop_id' => $active->id, 'product_id' => Product::factory()->create(['name' => 'Visible'])->id]);
        ShopProduct::factory()->hidden()->create(['shop_id' => $active->id, 'product_id' => Product::factory()->create(['name' => 'Hidden listing'])->id]);
        $inShopClosed = ShopProduct::factory()->create(['shop_id' => $inactive->id, 'product_id' => Product::factory()->create(['name' => 'Closed shop item'])->id]);

        $this->getJson('/api/storefront/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Visible');

        $this->getJson('/api/storefront/shops')->assertJsonCount(1, 'data');
        $this->getJson("/api/storefront/shops/{$inactive->slug}")->assertNotFound();
        $this->getJson("/api/storefront/shops/{$inactive->slug}/products")->assertNotFound();
        $this->getJson("/api/storefront/shops/{$inactive->slug}/products/{$inShopClosed->product_id}")->assertNotFound();
    }

    public function test_shop_specific_price_details_and_thumbnail_are_shown(): void
    {
        $product = Product::factory()->create([
            'name' => 'Catalog name',
            'description' => 'Catalog description',
            'base_price' => 100,
            'thumbnail' => 'products/catalog.png',
        ]);

        $shopA = Shop::factory()->create();
        $shopB = Shop::factory()->create();

        ShopProduct::factory()->create([
            'shop_id' => $shopA->id,
            'product_id' => $product->id,
            'price' => 120,
            'title' => 'Shop A name',
            'description' => 'Shop A description',
            'thumbnail' => 'listings/a.png',
        ]);
        ShopProduct::factory()->create(['shop_id' => $shopB->id, 'product_id' => $product->id, 'price' => 95]);

        $this->getJson("/api/storefront/shops/{$shopA->slug}/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Shop A name')
            ->assertJsonPath('data.description', 'Shop A description')
            ->assertJsonPath('data.price', 120)
            ->assertJsonPath('data.thumbnail_url', '/media/listings/a.png')
            ->assertJsonCount(1, 'other_shops')
            ->assertJsonPath('other_shops.0.price', 95);

        // Shop B has no overrides -> falls back to the catalog values, with its own price.
        $this->getJson("/api/storefront/shops/{$shopB->slug}/products")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Catalog name')
            ->assertJsonPath('data.0.description', 'Catalog description')
            ->assertJsonPath('data.0.price', 95)
            ->assertJsonPath('data.0.thumbnail_url', '/media/products/catalog.png');
    }

    public function test_product_page_returns_404_when_product_is_not_in_that_shop(): void
    {
        $shop = Shop::factory()->create();
        $product = Product::factory()->create();

        $this->getJson("/api/storefront/shops/{$shop->slug}/products/{$product->id}")->assertNotFound();
    }

    public function test_storefront_search_and_sort(): void
    {
        $shop = Shop::factory()->create();
        ShopProduct::factory()->create(['shop_id' => $shop->id, 'product_id' => Product::factory()->create(['name' => 'Red apple'])->id, 'price' => 30]);
        ShopProduct::factory()->create(['shop_id' => $shop->id, 'product_id' => Product::factory()->create(['name' => 'Green apple'])->id, 'price' => 10]);
        ShopProduct::factory()->create(['shop_id' => $shop->id, 'product_id' => Product::factory()->create(['name' => 'Banana'])->id, 'price' => 20]);

        $this->getJson("/api/storefront/shops/{$shop->slug}/products?search=apple&sort=price_asc")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Green apple')
            ->assertJsonPath('data.1.name', 'Red apple');
    }
}
