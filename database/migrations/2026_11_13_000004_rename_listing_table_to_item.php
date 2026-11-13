<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $isPgsql = DB::getDriverName() === 'pgsql';

        // 1. Rename listing table to item
        if (Schema::hasTable('listing') && ! Schema::hasTable('item')) {
            Schema::rename('listing', 'item');
        }

        // 2. Rename foreign key columns referencing listing to item_id
        if ($isPgsql) {
            if (Schema::hasTable('material_exchange')) {
                Schema::table('material_exchange', function (Blueprint $table): void {
                    if (Schema::hasColumn('material_exchange', 'listing_id') && ! Schema::hasColumn('material_exchange', 'item_id')) {
                        $table->renameColumn('listing_id', 'item_id');
                    }
                });
            }

            if (Schema::hasTable('marketplace_review')) {
                Schema::table('marketplace_review', function (Blueprint $table): void {
                    if (Schema::hasColumn('marketplace_review', 'listing_id') && ! Schema::hasColumn('marketplace_review', 'item_id')) {
                        $table->renameColumn('listing_id', 'item_id');
                    }
                });
            }
        } else {
            // For SQLite, recreate material_exchange and marketplace_review with item_id referencing item
            Schema::dropIfExists('material_exchange');
            Schema::create('material_exchange', function (Blueprint $table): void {
                $table->id();
                $table->string('invoice_number', 50)->nullable();
                $table->foreignId('item_id')->constrained('item')->cascadeOnDelete();
                $table->foreignId('provider_business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('requester_business_id')->constrained('business')->cascadeOnDelete();
                $table->decimal('quantity', 12, 2);
                $table->string('unit', 20)->default('pcs');
                $table->decimal('total_price', 10, 2)->default(0.00);
                $table->string('status', 30)->default('pending');
                $table->string('payment_status', 30)->default('pending_payment');
                $table->string('payment_reference', 100)->nullable();
                $table->timestamp('payment_requested_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });

            Schema::dropIfExists('marketplace_review');
            Schema::create('marketplace_review', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('item_id')->constrained('item')->cascadeOnDelete();
                $table->foreignId('seller_business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('reviewer_business_id')->constrained('business')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(5);
                $table->text('comment');
                $table->string('exchange_type', 30)->default('buy');
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('item') && ! Schema::hasTable('listing')) {
            Schema::rename('item', 'listing');
        }

        if (Schema::hasTable('material_exchange')) {
            Schema::table('material_exchange', function (Blueprint $table): void {
                if (Schema::hasColumn('material_exchange', 'item_id') && ! Schema::hasColumn('material_exchange', 'listing_id')) {
                    $table->renameColumn('item_id', 'listing_id');
                }
            });
        }

        if (Schema::hasTable('marketplace_review')) {
            Schema::table('marketplace_review', function (Blueprint $table): void {
                if (Schema::hasColumn('marketplace_review', 'item_id') && ! Schema::hasColumn('marketplace_review', 'listing_id')) {
                    $table->renameColumn('item_id', 'listing_id');
                }
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};
