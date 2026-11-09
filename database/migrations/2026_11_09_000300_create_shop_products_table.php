<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "listing": a catalog product assigned to a shop. It carries the
     * shop-specific price and optional overrides for title, details and
     * thumbnail. Only products that have a listing are visible to customers.
     */
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('title')->nullable();       // overrides products.name
            $table->text('description')->nullable();   // overrides products.description
            $table->string('thumbnail')->nullable();   // overrides products.thumbnail
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['shop_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};
