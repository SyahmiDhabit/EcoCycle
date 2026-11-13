<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        // 1. Rename password_reset_tokens to singular password_reset_token
        if (Schema::hasTable('password_reset_tokens') && ! Schema::hasTable('password_reset_token')) {
            Schema::rename('password_reset_tokens', 'password_reset_token');
        } elseif (! Schema::hasTable('password_reset_token')) {
            Schema::create('password_reset_token', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // 2. Drop all unused views to keep Supabase clean and strictly singular
        $views = [
            'admin_notifications',
            'business_notifications',
            'inventory_items',
            'inventory_transactions',
            'marketplace_listings',
            'marketplace_reviews',
            'material_exchanges',
            'waste_records',
        ];

        foreach ($views as $view) {
            if ($isPgsql) {
                DB::statement("DROP VIEW IF EXISTS {$view} CASCADE");
            } else {
                DB::statement("DROP VIEW IF EXISTS {$view}");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('password_reset_token') && ! Schema::hasTable('password_reset_tokens')) {
            Schema::rename('password_reset_token', 'password_reset_tokens');
        }
    }
};
