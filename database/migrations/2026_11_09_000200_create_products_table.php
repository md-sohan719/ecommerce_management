<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The shared product catalog. Products can be created by the admin or by
     * any shop owner and are visible to every shop owner in the catalog.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('sku', 64)->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2)->default(0); // suggested / default price
            $table->string('thumbnail')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
