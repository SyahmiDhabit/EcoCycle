<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_transactions')) {
            Schema::create('inventory_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('transaction_type', 30); // stock_in, stock_out, waste_disposed, marketplace_listed, adjustment
                $table->decimal('quantity_change', 12, 2);
                $table->decimal('quantity_after', 12, 2);
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'transaction_type']);
                $table->index(['inventory_item_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
