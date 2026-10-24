<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasTable('businesses')) {
            Schema::rename('users', 'businesses');
        }

        if (! Schema::hasTable('administrators')) {
            Schema::create('administrators', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('administrators');

        if (Schema::hasTable('businesses') && ! Schema::hasTable('users')) {
            Schema::rename('businesses', 'users');
        }
    }
};
