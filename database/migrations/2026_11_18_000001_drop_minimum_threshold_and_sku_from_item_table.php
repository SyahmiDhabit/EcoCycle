<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item')) {
            $columnsToDrop = [];
            if (Schema::hasColumn('item', 'minimum_threshold')) {
                $columnsToDrop[] = 'minimum_threshold';
            }
            if (Schema::hasColumn('item', 'sku')) {
                $columnsToDrop[] = 'sku';
            }
            if (! empty($columnsToDrop)) {
                Schema::table('item', function (Blueprint $table) use ($columnsToDrop): void {
                    $table->dropColumn($columnsToDrop);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item')) {
            Schema::table('item', function (Blueprint $table): void {
                if (! Schema::hasColumn('item', 'sku')) {
                    $table->string('sku', 100)->nullable();
                }
                if (! Schema::hasColumn('item', 'minimum_threshold')) {
                    $table->decimal('minimum_threshold', 12, 2)->nullable();
                }
            });
        }
    }
};
