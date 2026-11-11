<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business', function (Blueprint $table): void {
            if (!Schema::hasColumn('business', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business', function (Blueprint $table): void {
            if (Schema::hasColumn('business', 'profile_image')) {
                $table->dropColumn('profile_image');
            }
        });
    }
};
