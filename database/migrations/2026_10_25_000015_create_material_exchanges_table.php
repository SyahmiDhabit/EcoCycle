<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('material_exchanges')) {
            Schema::create('material_exchanges', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
                $table->foreignId('provider_business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('requester_business_id')->constrained('business')->cascadeOnDelete();
                $table->decimal('quantity', 12, 2);
                $table->string('unit', 20)->default('pcs');
                $table->decimal('total_price', 10, 2)->default(0.00);
                $table->string('status', 30)->default('pending'); // pending, accepted, completed, declined, cancelled
                $table->text('notes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['listing_id', 'status']);
                $table->index(['provider_business_id', 'status']);
                $table->index(['requester_business_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_exchanges');
    }
};
