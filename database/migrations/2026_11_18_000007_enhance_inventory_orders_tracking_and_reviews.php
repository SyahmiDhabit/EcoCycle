<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add original_quantity and ai_analysis snapshot to existing `item` table
        if (Schema::hasTable('item')) {
            Schema::table('item', function (Blueprint $table): void {
                if (! Schema::hasColumn('item', 'original_quantity')) {
                    $table->decimal('original_quantity', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('item', 'ai_analysis')) {
                    $table->json('ai_analysis')->nullable();
                }
            });

            DB::table('item')
                ->whereNull('original_quantity')
                ->update(['original_quantity' => DB::raw('quantity')]);
        }

        // 2. Enhance existing `material_exchange` table with purchase snapshots, collection address & delivery tracking
        if (Schema::hasTable('material_exchange')) {
            Schema::table('material_exchange', function (Blueprint $table): void {
                if (! Schema::hasColumn('material_exchange', 'item_name')) {
                    $table->string('item_name', 255)->nullable();
                }
                if (! Schema::hasColumn('material_exchange', 'seller_business_name')) {
                    $table->string('seller_business_name', 255)->nullable();
                }
                if (! Schema::hasColumn('material_exchange', 'buyer_business_name')) {
                    $table->string('buyer_business_name', 255)->nullable();
                }
                if (! Schema::hasColumn('material_exchange', 'collection_address')) {
                    $table->string('collection_address', 255)->nullable();
                }
                if (! Schema::hasColumn('material_exchange', 'fulfillment_method')) {
                    $table->string('fulfillment_method', 30)->default('collection');
                }
                if (! Schema::hasColumn('material_exchange', 'delivery_status')) {
                    $table->string('delivery_status', 40)->default('payment_pending');
                }
                if (! Schema::hasColumn('material_exchange', 'borrowed_returned_at')) {
                    $table->timestamp('borrowed_returned_at')->nullable();
                }
            });
        }

        // 3. Create singular `order_status_history` table for delivery status changes and timestamps
        if (! Schema::hasTable('order_status_history')) {
            Schema::create('order_status_history', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('material_exchange_id')->constrained('material_exchange')->cascadeOnDelete();
                $table->string('status', 40);
                $table->string('note', 255)->nullable();
                $table->foreignId('updated_by_business_id')->nullable()->constrained('business')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['material_exchange_id', 'created_at']);
            });
        }

        // 4. Add order reference to existing `marketplace_review` table (enforcing 1 review per delivered order)
        if (Schema::hasTable('marketplace_review')) {
            Schema::table('marketplace_review', function (Blueprint $table): void {
                if (! Schema::hasColumn('marketplace_review', 'material_exchange_id')) {
                    $table->foreignId('material_exchange_id')->nullable()->unique()->constrained('material_exchange')->nullOnDelete();
                }
            });
        }

        // 5. Enhance existing `invoice` table with receipt fields (unit_price, seller_address, contacts, payment_reference)
        if (Schema::hasTable('invoice')) {
            Schema::table('invoice', function (Blueprint $table): void {
                if (! Schema::hasColumn('invoice', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->default(0.00);
                }
                if (! Schema::hasColumn('invoice', 'seller_address')) {
                    $table->string('seller_address', 255)->nullable();
                }
                if (! Schema::hasColumn('invoice', 'seller_contact')) {
                    $table->string('seller_contact', 120)->nullable();
                }
                if (! Schema::hasColumn('invoice', 'buyer_contact')) {
                    $table->string('buyer_contact', 120)->nullable();
                }
                if (! Schema::hasColumn('invoice', 'payment_reference')) {
                    $table->string('payment_reference', 120)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoice')) {
            $cols = array_values(array_filter(
                ['unit_price', 'seller_address', 'seller_contact', 'buyer_contact', 'payment_reference'],
                fn (string $c): bool => Schema::hasColumn('invoice', $c)
            ));
            if (! empty($cols)) {
                Schema::table('invoice', function (Blueprint $table) use ($cols): void {
                    $table->dropColumn($cols);
                });
            }
        }

        if (Schema::hasTable('marketplace_review') && Schema::hasColumn('marketplace_review', 'material_exchange_id')) {
            Schema::table('marketplace_review', function (Blueprint $table): void {
                try {
                    $table->dropForeign(['material_exchange_id']);
                } catch (\Throwable) {
                }
                try {
                    $table->dropUnique(['material_exchange_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('material_exchange_id');
            });
        }

        Schema::dropIfExists('order_status_history');

        if (Schema::hasTable('material_exchange')) {
            $cols = array_values(array_filter(
                ['item_name', 'seller_business_name', 'buyer_business_name', 'collection_address', 'fulfillment_method', 'delivery_status', 'borrowed_returned_at'],
                fn (string $c): bool => Schema::hasColumn('material_exchange', $c)
            ));
            if (! empty($cols)) {
                Schema::table('material_exchange', function (Blueprint $table) use ($cols): void {
                    $table->dropColumn($cols);
                });
            }
        }

        if (Schema::hasTable('item')) {
            $cols = array_values(array_filter(
                ['original_quantity', 'ai_analysis'],
                fn (string $c): bool => Schema::hasColumn('item', $c)
            ));
            if (! empty($cols)) {
                Schema::table('item', function (Blueprint $table) use ($cols): void {
                    $table->dropColumn($cols);
                });
            }
        }
    }
};
