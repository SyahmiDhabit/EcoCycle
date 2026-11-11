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
                if (! Schema::hasColumn('marketplace_listings', 'images')) {
                    $table->json('images')->nullable()->after('image_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_listings')) {
            Schema::table('marketplace_listings', function (Blueprint $table): void {
                if (Schema::hasColumn('marketplace_listings', 'images')) {
                    $table->dropColumn('images');
                }
            });
        }
    }
};
