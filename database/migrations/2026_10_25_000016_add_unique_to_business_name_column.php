<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business', function (Blueprint $table): void {
            $table->unique('name', 'business_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('business', function (Blueprint $table): void {
            $table->dropUnique('business_name_unique');
        });
    }
};
