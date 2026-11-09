<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('business', 'remember_token')) {
            Schema::table('business', function (Blueprint $table): void {
                $table->dropColumn('remember_token');
            });
        }

        if (Schema::hasColumn('administrator', 'remember_token')) {
            Schema::table('administrator', function (Blueprint $table): void {
                $table->dropColumn('remember_token');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('business', 'remember_token')) {
            Schema::table('business', function (Blueprint $table): void {
                $table->rememberToken();
            });
        }

        if (! Schema::hasColumn('administrator', 'remember_token')) {
            Schema::table('administrator', function (Blueprint $table): void {
                $table->rememberToken();
            });
        }
    }
};
