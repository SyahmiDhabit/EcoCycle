<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('waste_records', 'cost_or_savings')) {
                $table->decimal('cost_or_savings', 10, 2)
                    ->nullable()
                    ->after('is_diverted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('waste_records', function (Blueprint $table): void {
            if (Schema::hasColumn('waste_records', 'cost_or_savings')) {
                $table->dropColumn('cost_or_savings');
            }
        });
    }
};
