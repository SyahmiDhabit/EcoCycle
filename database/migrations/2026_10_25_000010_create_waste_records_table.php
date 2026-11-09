<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('waste_records')) {
            Schema::create('waste_records', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->string('material_name');
                $table->string('waste_type', 60);
                $table->decimal('quantity', 12, 2);
                $table->string('unit', 20)->default('kg');
                $table->string('disposal_method', 60);
                $table->boolean('is_diverted')->default(true);
                $table->text('notes')->nullable();
                $table->date('recorded_at');
                $table->timestamps();

                $table->index(['business_id', 'recorded_at']);
                $table->index(['business_id', 'is_diverted']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_records');
    }
};
