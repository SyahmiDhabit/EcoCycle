<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('item')) {
            return;
        }

        $columnsToDrop = [];
        if (Schema::hasColumn('item', 'image_4')) {
            $columnsToDrop[] = 'image_4';
        }
        if (Schema::hasColumn('item', 'image_5')) {
            $columnsToDrop[] = 'image_5';
        }

        if (! empty($columnsToDrop)) {
            Schema::table('item', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }

        if (Schema::hasColumn('item', 'category')) {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE item ALTER COLUMN category TYPE VARCHAR(100)');
            } elseif ($driver === 'mysql') {
                DB::statement('ALTER TABLE item MODIFY category VARCHAR(100)');
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('item')) {
            return;
        }

        Schema::table('item', function (Blueprint $table): void {
            if (! Schema::hasColumn('item', 'image_4')) {
                $table->string('image_4')->nullable();
            }
            if (! Schema::hasColumn('item', 'image_5')) {
                $table->string('image_5')->nullable();
            }
        });
    }
};
