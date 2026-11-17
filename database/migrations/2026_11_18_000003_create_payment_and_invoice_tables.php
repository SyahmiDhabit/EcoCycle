<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('material_exchange')) {
            Schema::table('material_exchange', function (Blueprint $table): void {
                if (! Schema::hasColumn('material_exchange', 'order_reference')) {
                    $table->string('order_reference', 50)->nullable()->unique();
                }
                if (! Schema::hasColumn('material_exchange', 'listing_type')) {
                    $table->string('listing_type', 30)->default('buy');
                }
                if (! Schema::hasColumn('material_exchange', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->default(0.00);
                }
            });
        }

        if (! Schema::hasTable('payment')) {
            Schema::create('payment', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('material_exchange_id')->constrained('material_exchange')->cascadeOnDelete();
                $table->foreignId('buyer_business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('seller_business_id')->constrained('business')->cascadeOnDelete();
                $table->string('provider', 30)->default('stripe');
                $table->string('settlement_account_type', 40)->default('platform_test_account');
                $table->string('stripe_checkout_session_id', 120)->nullable()->unique();
                $table->string('stripe_payment_intent_id', 120)->nullable()->unique();
                $table->string('stripe_event_id', 120)->nullable()->unique();
                $table->string('idempotency_key', 120)->nullable()->unique();
                $table->string('currency', 10)->default('myr');
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->unsignedBigInteger('amount_sen')->default(0);
                $table->string('payment_method', 50)->nullable();
                $table->string('status', 30)->default('pending'); // pending, paid, failed, cancelled, expired
                $table->string('failure_reason', 255)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['material_exchange_id', 'status']);
                $table->index(['buyer_business_id', 'status']);
                $table->index(['seller_business_id', 'status']);
            });
        }

        if (! Schema::hasTable('invoice')) {
            Schema::create('invoice', function (Blueprint $table): void {
                $table->id();
                $table->string('invoice_number', 40)->unique();
                $table->foreignId('material_exchange_id')->unique()->constrained('material_exchange')->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payment')->nullOnDelete();
                $table->foreignId('seller_business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('buyer_business_id')->constrained('business')->cascadeOnDelete();
                $table->string('order_reference', 50);
                $table->string('currency', 10)->default('myr');
                $table->string('item_name', 255);
                $table->text('item_description')->nullable();
                $table->decimal('quantity', 12, 2);
                $table->string('unit', 30)->default('pcs');
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->decimal('subtotal', 10, 2)->default(0.00);
                $table->string('tax_label', 40)->nullable();
                $table->decimal('tax_rate', 5, 2)->nullable();
                $table->decimal('tax_amount', 10, 2)->default(0.00);
                $table->decimal('total_amount', 10, 2)->default(0.00);
                $table->string('payment_status', 30)->default('paid');
                $table->string('payment_method', 50)->nullable();
                $table->string('payment_reference', 120)->nullable();
                $table->timestamp('issued_at');
                $table->timestamp('paid_at')->nullable();
                $table->json('seller_snapshot')->nullable();
                $table->json('buyer_snapshot')->nullable();
                $table->timestamps();

                $table->index(['seller_business_id', 'issued_at']);
                $table->index(['buyer_business_id', 'issued_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice');
        Schema::dropIfExists('payment');

        if (Schema::hasTable('material_exchange')) {
            Schema::table('material_exchange', function (Blueprint $table): void {
                $cols = [];
                foreach (['order_reference', 'listing_type', 'unit_price'] as $col) {
                    if (Schema::hasColumn('material_exchange', $col)) {
                        $cols[] = $col;
                    }
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
