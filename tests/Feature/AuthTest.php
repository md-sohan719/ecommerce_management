<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receives_a_token(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.role', 'admin');

        $this->withToken($response->json('token'))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->postJson('/api/login', ['email' => 'owner@example.com', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_guests_cannot_access_protected_endpoints(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/admin/shops')->assertUnauthorized();
        $this->getJson('/api/owner/catalog')->assertUnauthorized();
    }

    public function test_shop_owner_cannot_access_admin_panel(): void
    {
        Sanctum::actingAs(User::factory()->shopOwner()->create());

        $this->getJson('/api/admin/shops')->assertForbidden();
        $this->postJson('/api/admin/products', ['name' => 'X', 'base_price' => 1])->assertForbidden();
    }

    public function test_admin_cannot_access_shop_owner_panel(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/owner/catalog')->assertForbidden();
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
