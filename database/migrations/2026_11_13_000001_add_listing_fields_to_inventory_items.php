<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('inventory_items', 'price')) {
                    $table->decimal('price', 10, 2)->nullable()->after('reusability');
                }
                if (! Schema::hasColumn('inventory_items', 'listing_type')) {
                    $table->string('listing_type', 30)->default('buy')->nullable()->after('price');
                }
                if (! Schema::hasColumn('inventory_items', 'condition')) {
                    $table->string('condition', 40)->default('Used - Good')->nullable()->after('listing_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                if (Schema::hasColumn('inventory_items', 'condition')) {
                    $table->dropColumn('condition');
                }
                if (Schema::hasColumn('inventory_items', 'listing_type')) {
                    $table->dropColumn('listing_type');
                }
                if (Schema::hasColumn('inventory_items', 'price')) {
                    $table->dropColumn('price');
                }
            });
        }
    }
};
