<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_transaction') && ! Schema::hasTable('item_transaction')) {
            Schema::rename('inventory_transaction', 'item_transaction');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item_transaction') && ! Schema::hasTable('inventory_transaction')) {
            Schema::rename('item_transaction', 'inventory_transaction');
        }
    }
};
