<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business', function (Blueprint $table): void {
            if (! Schema::hasColumn('business', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('review_note')
                    ->constrained('administrator')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('business', 'reviewed_at')) {
                $table->timestamp('reviewed_at')
                    ->nullable()
                    ->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business', function (Blueprint $table): void {
            if (Schema::hasColumn('business', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
                $table->dropColumn('reviewed_by');
            }

            if (Schema::hasColumn('business', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};
