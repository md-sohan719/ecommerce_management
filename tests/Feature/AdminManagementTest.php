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

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
        Sanctum::actingAs($this->admin);
    }

    public function test_admin_can_crud_shops(): void
    {
        $owner = User::factory()->shopOwner()->create();

        $id = $this->postJson('/api/admin/shops', [
            'name' => 'My Shop',
            'owner_id' => $owner->id,
            'address' => 'Dhaka',
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'my-shop')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.owner.id', $owner->id)
            ->json('data.id');

        $this->getJson('/api/admin/shops')->assertOk()->assertJsonCount(1, 'data');

        $this->putJson("/api/admin/shops/{$id}", ['name' => 'Renamed Shop', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.slug', 'renamed-shop')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/admin/shops/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('shops', ['id' => $id]);
    }

    public function test_shop_owner_must_be_a_shop_owner_account(): void
    {
        $this->postJson('/api/admin/shops', ['name' => 'Shop', 'owner_id' => $this->admin->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('owner_id');
    }

    public function test_admin_can_crud_products_with_thumbnail(): void
    {
        $response = $this->post('/api/admin/products', [
            'name' => 'Phone',
            'sku' => 'PH-1',
            'base_price' => 999.5,
            'thumbnail' => UploadedFile::fake()->image('phone.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $product = Product::findOrFail($response->json('data.id'));
        $this->assertSame($this->admin->id, $product->created_by);
        Storage::disk('public')->assertExists($product->thumbnail);
        $this->assertSame('/media/'.$product->thumbnail, $response->json('data.thumbnail_url'));

        // Update via multipart with method spoofing (as the SPA does), replacing the thumbnail
        $old = $product->thumbnail;
        $this->post("/api/admin/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Phone X',
            'base_price' => 1099,
            'thumbnail' => UploadedFile::fake()->image('new.png'),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.name', 'Phone X');

        Storage::disk('public')->assertMissing($old);
        $product->refresh();
        Storage::disk('public')->assertExists($product->thumbnail);

        // Remove thumbnail
        $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Phone X',
            'base_price' => 1099,
            'remove_thumbnail' => true,
        ])->assertOk()->assertJsonPath('data.thumbnail_url', null);

        $this->deleteJson("/api/admin/products/{$product->id}")->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_deleting_a_product_removes_its_listings(): void
    {
        $listing = ShopProduct::factory()->create();

        $this->deleteJson("/api/admin/products/{$listing->product_id}")->assertNoContent();

        $this->assertDatabaseMissing('shop_products', ['id' => $listing->id]);
    }

    public function test_product_validation(): void
    {
        $this->postJson('/api/admin/products', ['base_price' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'base_price']);

        Product::factory()->create(['sku' => 'DUP']);
        $this->postJson('/api/admin/products', ['name' => 'X', 'base_price' => 1, 'sku' => 'DUP'])
            ->assertJsonValidationErrors('sku');
    }

    public function test_admin_can_manage_shop_owner_accounts(): void
    {
        $id = $this->postJson('/api/admin/owners', [
            'name' => 'New Owner',
            'email' => 'new@example.com',
            'password' => 'secret123',
        ])->assertCreated()->assertJsonPath('data.role', 'shop_owner')->json('data.id');

        // New owner can log in
        $this->postJson('/api/login', ['email' => 'new@example.com', 'password' => 'secret123'])->assertOk();

        // Update without password keeps the old one
        $this->putJson("/api/admin/owners/{$id}", ['name' => 'Renamed', 'email' => 'new@example.com', 'password' => ''])
            ->assertOk()->assertJsonPath('data.name', 'Renamed');
        $this->postJson('/api/login', ['email' => 'new@example.com', 'password' => 'secret123'])->assertOk();

        $shop = Shop::factory()->create(['owner_id' => $id]);

        $this->deleteJson("/api/admin/owners/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $id]);
        $this->assertNull($shop->fresh()->owner_id); // shop is kept, ready to be reassigned
    }

    public function test_owner_endpoints_do_not_expose_admin_accounts(): void
    {
        $this->getJson("/api/admin/owners/{$this->admin->id}")->assertNotFound();
        $this->deleteJson("/api/admin/owners/{$this->admin->id}")->assertNotFound();
    }

    public function test_stats(): void
    {
        ShopProduct::factory()->create();
        Product::factory()->create();

        $this->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJson(['shops' => 1, 'products' => 2, 'unlisted_products' => 1, 'listings' => 1]);
    }
}
