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
                if (! Schema::hasColumn('inventory_items', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                if (Schema::hasColumn('inventory_items', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }
};
