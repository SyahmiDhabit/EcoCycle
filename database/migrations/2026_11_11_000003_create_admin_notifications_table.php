<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_notifications')) {
            Schema::create('admin_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('admin_id')->nullable()->constrained('administrator')->cascadeOnDelete();
                $table->foreignId('business_id')->nullable()->constrained('business')->cascadeOnDelete();
                $table->string('type', 50)->default('general');
                $table->string('title');
                $table->text('message');
                $table->string('link')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['admin_id', 'is_read']);
                $table->index(['is_read', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
