<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopOwnerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->owner = User::factory()->shopOwner()->create();
        $this->shop = Shop::factory()->create(['owner_id' => $this->owner->id]);
    }

    public function test_catalog_contains_products_from_admin_and_other_owners(): void
    {
        $adminProduct = Product::factory()->create();
        $otherOwnerProduct = Product::factory()->create(['created_by' => User::factory()->shopOwner()]);
        ShopProduct::factory()->create(['shop_id' => $this->shop->id, 'product_id' => $adminProduct->id]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/owner/catalog?shop_id={$this->shop->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $listed = collect($response->json('data'))->pluck('is_listed', 'id');
        $this->assertTrue($listed[$adminProduct->id]);
        $this->assertFalse($listed[$otherOwnerProduct->id]);

        $this->getJson("/api/owner/catalog?shop_id={$this->shop->id}&availability=not_listed")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $otherOwnerProduct->id);
    }

    public function test_owner_can_add_catalog_product_to_shop_with_own_price_details_and_thumbnail(): void
    {
        $product = Product::factory()->create(['base_price' => 100]);
        Sanctum::actingAs($this->owner);

        $this->post("/api/owner/shops/{$this->shop->id}/listings", [
            'product_id' => $product->id,
            'price' => 150,
            'title' => 'My special title',
            'description' => 'My details',
            'thumbnail' => UploadedFile::fake()->image('mine.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.price', 150)
            ->assertJsonPath('data.effective.name', 'My special title')
            ->assertJsonPath('data.is_active', true);

        // Customers see the shop-specific values
        $this->getJson("/api/storefront/shops/{$this->shop->slug}/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.price', 150)
            ->assertJsonPath('data.name', 'My special title');

        // Can't add the same product twice
        $this->postJson("/api/owner/shops/{$this->shop->id}/listings", ['product_id' => $product->id, 'price' => 1])
            ->assertJsonValidationErrors('product_id');
    }

    public function test_owner_can_update_and_remove_listing(): void
    {
        $listing = ShopProduct::factory()->create(['shop_id' => $this->shop->id, 'price' => 10]);
        Sanctum::actingAs($this->owner);

        $this->putJson("/api/owner/shops/{$this->shop->id}/listings/{$listing->id}", [
            'price' => 25.5,
            'title' => null,
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.price', 25.5)->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/owner/shops/{$this->shop->id}/listings/{$listing->id}")->assertNoContent();
        $this->assertDatabaseMissing('shop_products', ['id' => $listing->id]);
        $this->assertDatabaseHas('products', ['id' => $listing->product_id]); // catalog product stays
    }

    public function test_owner_cannot_manage_another_owners_shop(): void
    {
        $otherShop = Shop::factory()->create();
        $otherListing = ShopProduct::factory()->create(['shop_id' => $otherShop->id]);
        $product = Product::factory()->create();
        Sanctum::actingAs($this->owner);

        $this->getJson("/api/owner/shops/{$otherShop->id}/listings")->assertForbidden();
        $this->postJson("/api/owner/shops/{$otherShop->id}/listings", ['product_id' => $product->id, 'price' => 1])->assertForbidden();
        $this->putJson("/api/owner/shops/{$otherShop->id}/listings/{$otherListing->id}", ['price' => 1])->assertForbidden();
        $this->deleteJson("/api/owner/shops/{$otherShop->id}/listings/{$otherListing->id}")->assertForbidden();

        // Using their own shop id with someone else's listing id is not found (scoped binding)
        $this->putJson("/api/owner/shops/{$this->shop->id}/listings/{$otherListing->id}", ['price' => 1])->assertNotFound();

        $this->assertSame((float) $otherListing->price, (float) $otherListing->fresh()->price);
    }

    public function test_owner_created_product_joins_shared_catalog_and_can_be_listed_by_other_owners(): void
    {
        Sanctum::actingAs($this->owner);

        $productId = $this->postJson('/api/owner/products', [
            'name' => 'Owner made product',
            'base_price' => 200,
            'shop_id' => $this->shop->id,
            'price' => 210,
        ])->assertCreated()
            ->assertJsonPath('data.created_by', $this->owner->id)
            ->assertJsonPath('data.listings_count', 1)
            ->json('data.id');

        $this->assertDatabaseHas('shop_products', ['shop_id' => $this->shop->id, 'product_id' => $productId, 'price' => 210]);

        // Another shop owner sees it in the catalog and lists it with their own price
        $other = User::factory()->shopOwner()->create();
        $otherShop = Shop::factory()->create(['owner_id' => $other->id]);
        Sanctum::actingAs($other);

        $this->getJson('/api/owner/catalog?search=Owner made')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.can_edit', false);

        $this->postJson("/api/owner/shops/{$otherShop->id}/listings", ['product_id' => $productId, 'price' => 190])
            ->assertCreated();

        $this->getJson("/api/storefront/shops/{$otherShop->slug}/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.price', 190)
            ->assertJsonPath('other_shops.0.price', 210);
    }

    public function test_owner_cannot_list_new_product_in_someone_elses_shop(): void
    {
        $otherShop = Shop::factory()->create();
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/owner/products', ['name' => 'X', 'base_price' => 1, 'shop_id' => $otherShop->id])
            ->assertJsonValidationErrors('shop_id');
    }

    public function test_owner_can_only_edit_and_delete_products_they_created(): void
    {
        $mine = Product::factory()->create(['created_by' => $this->owner->id]);
        $adminProduct = Product::factory()->create();
        Sanctum::actingAs($this->owner);

        $this->putJson("/api/owner/products/{$mine->id}", ['name' => 'Renamed', 'base_price' => 5])
            ->assertOk()->assertJsonPath('data.name', 'Renamed');

        $this->putJson("/api/owner/products/{$adminProduct->id}", ['name' => 'Hacked', 'base_price' => 5])->assertForbidden();
        $this->deleteJson("/api/owner/products/{$adminProduct->id}")->assertForbidden();

        $this->deleteJson("/api/owner/products/{$mine->id}")->assertNoContent();
    }

    public function test_owner_cannot_delete_own_product_while_other_shops_sell_it(): void
    {
        $mine = Product::factory()->create(['created_by' => $this->owner->id]);
        ShopProduct::factory()->create(['shop_id' => $this->shop->id, 'product_id' => $mine->id]);
        ShopProduct::factory()->create(['product_id' => $mine->id]); // someone else's shop
        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/owner/products/{$mine->id}")->assertUnprocessable();
        $this->assertDatabaseHas('products', ['id' => $mine->id]);
    }

    public function test_owner_only_sees_their_own_shops(): void
    {
        Shop::factory()->create(); // someone else's
        Shop::factory()->create(['owner_id' => $this->owner->id]);
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/owner/shops')->assertOk()->assertJsonCount(2, 'data');
    }
}
