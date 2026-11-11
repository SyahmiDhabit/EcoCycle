<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_items') && ! Schema::hasColumn('inventory_items', 'reusability')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                $table->string('reusability', 30)->default('reusable')->after('status');
                $table->index(['business_id', 'reusability']);
            });
        }

        if (Schema::hasTable('marketplace_listings')) {
            Schema::table('marketplace_listings', function (Blueprint $table): void {
                if (! Schema::hasColumn('marketplace_listings', 'listing_type')) {
                    $table->string('listing_type', 30)->default('buy')->after('is_free');
                    $table->index(['status', 'listing_type']);
                }

                if (! Schema::hasColumn('marketplace_listings', 'reusability')) {
                    $table->string('reusability', 30)->default('reusable')->after('listing_type');
                    $table->index(['status', 'reusability']);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_items') && Schema::hasColumn('inventory_items', 'reusability')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                $table->dropIndex(['business_id', 'reusability']);
                $table->dropColumn('reusability');
            });
        }

        if (Schema::hasTable('marketplace_listings')) {
            Schema::table('marketplace_listings', function (Blueprint $table): void {
                if (Schema::hasColumn('marketplace_listings', 'listing_type')) {
                    $table->dropIndex(['status', 'listing_type']);
                    $table->dropColumn('listing_type');
                }

                if (Schema::hasColumn('marketplace_listings', 'reusability')) {
                    $table->dropIndex(['status', 'reusability']);
                    $table->dropColumn('reusability');
                }
            });
        }
    }
};
