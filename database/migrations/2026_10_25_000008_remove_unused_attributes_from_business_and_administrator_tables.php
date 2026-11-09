<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('business', 'email_verified_at')) {
            Schema::table('business', function (Blueprint $table): void {
                $table->dropColumn('email_verified_at');
            });
        }

        if (Schema::hasColumn('administrator', 'email_verified_at')) {
            Schema::table('administrator', function (Blueprint $table): void {
                $table->dropColumn('email_verified_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('business', 'email_verified_at')) {
            Schema::table('business', function (Blueprint $table): void {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('administrator', 'email_verified_at')) {
            Schema::table('administrator', function (Blueprint $table): void {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            });
        }
    }
};
