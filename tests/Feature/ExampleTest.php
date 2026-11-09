<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_spa_shell_is_served_for_frontend_routes(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk()->assertSee('id="app"', false);
        $this->get('/admin/shops')->assertOk()->assertSee('id="app"', false);
        $this->get('/shops/some-shop/products/1')->assertOk();
    }

    public function test_unknown_api_routes_return_json_404(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }
}
