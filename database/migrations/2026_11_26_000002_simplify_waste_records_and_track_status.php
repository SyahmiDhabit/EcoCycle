<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasLegacyView = Schema::hasView('waste_records');
        if ($hasLegacyView) {
            DB::statement('DROP VIEW waste_records');
        }

        $obsoleteColumns = array_values(array_filter(
            ['disposal_method', 'cost_or_savings', 'ai_recommendation'],
            fn (string $column): bool => Schema::hasColumn('waste_record', $column),
        ));

        Schema::table('waste_record', function (Blueprint $table) use ($obsoleteColumns): void {
            $table->json('tracking_history')->nullable();
            if ($obsoleteColumns !== []) {
                $table->dropColumn($obsoleteColumns);
            }
        });

        if ($hasLegacyView) {
            DB::statement('CREATE VIEW waste_records AS SELECT * FROM waste_record');
        }
    }
};
