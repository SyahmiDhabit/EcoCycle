<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_record', function (Blueprint $table): void {
            $table->decimal('facility_payment_rm', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('waste_record', function (Blueprint $table): void {
            $table->dropColumn('facility_payment_rm');
        });
    }
};
