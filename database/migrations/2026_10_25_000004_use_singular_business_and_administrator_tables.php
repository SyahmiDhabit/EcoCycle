<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('businesses') && ! Schema::hasTable('business')) {
            Schema::rename('businesses', 'business');
        }

        if (Schema::hasTable('administrators') && ! Schema::hasTable('administrator')) {
            Schema::rename('administrators', 'administrator');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business') && ! Schema::hasTable('businesses')) {
            Schema::rename('business', 'businesses');
        }

        if (Schema::hasTable('administrator') && ! Schema::hasTable('administrators')) {
            Schema::rename('administrator', 'administrators');
        }
    }
};
