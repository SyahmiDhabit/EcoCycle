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
                if (! Schema::hasColumn('inventory_items', 'image_path')) {
                    $table->string('image_path')->nullable()->after('reusability');
                }
                if (! Schema::hasColumn('inventory_items', 'images')) {
                    $table->json('images')->nullable()->after('image_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                if (Schema::hasColumn('inventory_items', 'images')) {
                    $table->dropColumn('images');
                }
                if (Schema::hasColumn('inventory_items', 'image_path')) {
                    $table->dropColumn('image_path');
                }
            });
        }
    }
};
