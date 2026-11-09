<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->string('category', 60);
                $table->decimal('quantity', 12, 2)->default(0);
                $table->string('unit', 20)->default('pcs');
                $table->decimal('minimum_threshold', 12, 2)->nullable();
                $table->string('status', 30)->default('in_stock');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status']);
                $table->index(['business_id', 'category']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
