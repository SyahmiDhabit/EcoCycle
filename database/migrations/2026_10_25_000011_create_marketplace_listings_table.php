<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_listings')) {
            Schema::create('marketplace_listings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->string('title');
                $table->text('description');
                $table->string('category', 60);
                $table->decimal('quantity', 12, 2);
                $table->string('unit', 20)->default('pcs');
                $table->decimal('price', 10, 2)->nullable();
                $table->boolean('is_free')->default(false);
                $table->string('condition', 40)->default('Used - Good');
                $table->string('location_city', 100);
                $table->string('location_state', 40);
                $table->string('image_path')->nullable();
                $table->string('status', 30)->default('active');
                $table->timestamps();

                $table->index(['status', 'category']);
                $table->index(['business_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
