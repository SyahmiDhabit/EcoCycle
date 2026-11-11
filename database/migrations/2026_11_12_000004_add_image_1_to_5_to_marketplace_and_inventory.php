<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_listings')) {
            Schema::table('marketplace_listings', function (Blueprint $table): void {
                if (! Schema::hasColumn('marketplace_listings', 'image_1')) {
                    $table->string('image_1')->nullable()->after('image_path');
                }
                if (! Schema::hasColumn('marketplace_listings', 'image_2')) {
                    $table->string('image_2')->nullable()->after('image_1');
                }
                if (! Schema::hasColumn('marketplace_listings', 'image_3')) {
                    $table->string('image_3')->nullable()->after('image_2');
                }
                if (! Schema::hasColumn('marketplace_listings', 'image_4')) {
                    $table->string('image_4')->nullable()->after('image_3');
                }
                if (! Schema::hasColumn('marketplace_listings', 'image_5')) {
                    $table->string('image_5')->nullable()->after('image_4');
                }
            });
        }

        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('inventory_items', 'image_1')) {
                    $table->string('image_1')->nullable()->after('image_path');
                }
                if (! Schema::hasColumn('inventory_items', 'image_2')) {
                    $table->string('image_2')->nullable()->after('image_1');
                }
                if (! Schema::hasColumn('inventory_items', 'image_3')) {
                    $table->string('image_3')->nullable()->after('image_2');
                }
                if (! Schema::hasColumn('inventory_items', 'image_4')) {
                    $table->string('image_4')->nullable()->after('image_3');
                }
                if (! Schema::hasColumn('inventory_items', 'image_5')) {
                    $table->string('image_5')->nullable()->after('image_4');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_listings')) {
            Schema::table('marketplace_listings', function (Blueprint $table): void {
                $columns = ['image_1', 'image_2', 'image_3', 'image_4', 'image_5'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('marketplace_listings', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                $columns = ['image_1', 'image_2', 'image_3', 'image_4', 'image_5'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('inventory_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
