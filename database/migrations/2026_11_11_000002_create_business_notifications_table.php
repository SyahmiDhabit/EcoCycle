<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_notifications')) {
            Schema::create('business_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->string('type', 50)->default('general');
                $table->string('title');
                $table->text('message');
                $table->string('link')->nullable();
                $table->string('icon_type', 30)->default('green');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'is_read']);
                $table->index(['business_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_notifications');
    }
};
