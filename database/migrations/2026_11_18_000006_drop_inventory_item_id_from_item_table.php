<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item')) {
            // Safely drop the redundant self-referencing inventory_item_id column from `item`.
            // Note: waste_records.inventory_item_id and inventory_transactions.inventory_item_id
            // remain untouched as valid foreign keys referencing item.id.
            if (Schema::hasColumn('item', 'inventory_item_id')) {
                Schema::table('item', function (Blueprint $table): void {
                    $table->dropColumn('inventory_item_id');
                });
            }

            // Unpublished inventory items should not carry a misleading RM 0.00 sell price until configured.
            if (Schema::hasColumn('item', 'price') && Schema::hasColumn('item', 'status')) {
                DB::table('item')
                    ->whereIn('status', ['in_stock', 'out_of_stock'])
                    ->where('listing_type', 'buy')
                    ->where('price', '<=', 0)
                    ->update(['price' => null]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item') && ! Schema::hasColumn('item', 'inventory_item_id')) {
            Schema::table('item', function (Blueprint $table): void {
                $table->unsignedBigInteger('inventory_item_id')->nullable();
            });
        }
    }
};
