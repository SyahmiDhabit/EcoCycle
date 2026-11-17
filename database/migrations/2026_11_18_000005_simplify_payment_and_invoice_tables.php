<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. Simplify `payment` table: keep only essential payment columns and enforce 1 payment row per order
        if (Schema::hasTable('payment')) {
            // Safely deduplicate any historical multiple payment rows per order (keep paid first, then latest id)
            $duplicateOrderIds = DB::table('payment')
                ->select('material_exchange_id')
                ->groupBy('material_exchange_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('material_exchange_id');

            foreach ($duplicateOrderIds as $orderId) {
                $rows = DB::table('payment')
                    ->where('material_exchange_id', $orderId)
                    ->orderByRaw("CASE WHEN status = 'paid' THEN 0 WHEN status = 'refunded' THEN 1 ELSE 2 END")
                    ->orderByDesc('id')
                    ->get();

                $keep = $rows->first();
                if (! $keep) {
                    continue;
                }

                $dropIds = $rows->skip(1)->pluck('id')->all();
                if (! empty($dropIds)) {
                    if (Schema::hasTable('invoice') && Schema::hasColumn('invoice', 'payment_id')) {
                        DB::table('invoice')
                            ->whereIn('payment_id', $dropIds)
                            ->update(['payment_id' => $keep->id]);
                    }
                    DB::table('payment')->whereIn('id', $dropIds)->delete();
                }
            }

            // Normalize any legacy 'expired' payment status to 'cancelled'
            if (Schema::hasColumn('payment', 'status')) {
                DB::table('payment')->where('status', 'expired')->update(['status' => 'cancelled']);
            }

            // Drop unique index on idempotency_key before dropping the column (required for SQLite)
            if (Schema::hasColumn('payment', 'idempotency_key')) {
                try {
                    Schema::table('payment', function (Blueprint $table): void {
                        $table->dropUnique('payment_idempotency_key_unique');
                    });
                } catch (\Throwable) {
                    // Index may not exist in all environments
                }
            }

            $paymentColsToDrop = array_values(array_filter([
                'provider',
                'settlement_account_type',
                'idempotency_key',
                'amount_sen',
                'payment_method',
                'failure_reason',
            ], fn (string $col): bool => Schema::hasColumn('payment', $col)));

            if (! empty($paymentColsToDrop)) {
                Schema::table('payment', function (Blueprint $table) use ($paymentColsToDrop): void {
                    $table->dropColumn($paymentColsToDrop);
                });
            }

            // Enforce unique material_exchange_id on payment table to prevent duplicate payment rows per order
            try {
                Schema::table('payment', function (Blueprint $table): void {
                    $table->unique('material_exchange_id', 'payment_material_exchange_id_unique');
                });
            } catch (\Throwable) {
                // Unique index already exists
            }
        }

        // 2. Simplify `invoice` table: store clean immutable receipt snapshot fields only
        if (Schema::hasTable('invoice')) {
            Schema::table('invoice', function (Blueprint $table): void {
                if (! Schema::hasColumn('invoice', 'buyer_business_name')) {
                    $table->string('buyer_business_name', 255)->default('Buyer Business');
                }
                if (! Schema::hasColumn('invoice', 'seller_business_name')) {
                    $table->string('seller_business_name', 255)->default('Seller Business');
                }
                if (! Schema::hasColumn('invoice', 'pickup_address')) {
                    $table->string('pickup_address', 255)->nullable();
                }
            });

            // Migrate any existing buyer/seller snapshot names before dropping JSON snapshot columns
            if (Schema::hasColumn('invoice', 'seller_snapshot') || Schema::hasColumn('invoice', 'buyer_snapshot')) {
                $existingInvoices = DB::table('invoice')->get();
                foreach ($existingInvoices as $inv) {
                    $sellerSnap = is_string($inv->seller_snapshot ?? null) ? json_decode($inv->seller_snapshot, true) : null;
                    $buyerSnap = is_string($inv->buyer_snapshot ?? null) ? json_decode($inv->buyer_snapshot, true) : null;

                    $sellerName = $sellerSnap['business_name']
                        ?? DB::table('business')->where('id', $inv->seller_business_id)->value('business_name')
                        ?? 'Seller Business';

                    $buyerName = $buyerSnap['business_name']
                        ?? DB::table('business')->where('id', $inv->buyer_business_id)->value('business_name')
                        ?? 'Buyer Business';

                    DB::table('invoice')
                        ->where('id', $inv->id)
                        ->update([
                            'seller_business_name' => $sellerName,
                            'buyer_business_name' => $buyerName,
                        ]);
                }
            }

            $invoiceColsToDrop = array_values(array_filter([
                'order_reference',
                'item_description',
                'unit_price',
                'subtotal',
                'tax_label',
                'tax_rate',
                'tax_amount',
                'payment_method',
                'payment_reference',
                'paid_at',
                'seller_snapshot',
                'buyer_snapshot',
            ], fn (string $col): bool => Schema::hasColumn('invoice', $col)));

            if (! empty($invoiceColsToDrop)) {
                Schema::table('invoice', function (Blueprint $table) use ($invoiceColsToDrop): void {
                    $table->dropColumn($invoiceColsToDrop);
                });
            }
        }

        // 3. Normalize pending_payment status in material_exchange to clean 'pending'
        if (Schema::hasTable('material_exchange') && Schema::hasColumn('material_exchange', 'payment_status')) {
            DB::table('material_exchange')
                ->where('payment_status', 'pending_payment')
                ->update(['payment_status' => 'pending']);

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE material_exchange ALTER COLUMN payment_status SET DEFAULT 'pending'");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payment')) {
            try {
                Schema::table('payment', function (Blueprint $table): void {
                    $table->dropUnique('payment_material_exchange_id_unique');
                });
            } catch (\Throwable) {
                // Ignore if already dropped
            }

            Schema::table('payment', function (Blueprint $table): void {
                if (! Schema::hasColumn('payment', 'provider')) {
                    $table->string('provider', 30)->default('stripe');
                }
                if (! Schema::hasColumn('payment', 'settlement_account_type')) {
                    $table->string('settlement_account_type', 40)->default('platform_test_account');
                }
                if (! Schema::hasColumn('payment', 'idempotency_key')) {
                    $table->string('idempotency_key', 120)->nullable()->unique();
                }
                if (! Schema::hasColumn('payment', 'amount_sen')) {
                    $table->unsignedBigInteger('amount_sen')->default(0);
                }
                if (! Schema::hasColumn('payment', 'payment_method')) {
                    $table->string('payment_method', 50)->nullable();
                }
                if (! Schema::hasColumn('payment', 'failure_reason')) {
                    $table->string('failure_reason', 255)->nullable();
                }
            });
        }

        if (Schema::hasTable('invoice')) {
            Schema::table('invoice', function (Blueprint $table): void {
                if (! Schema::hasColumn('invoice', 'order_reference')) {
                    $table->string('order_reference', 50)->default('');
                }
                if (! Schema::hasColumn('invoice', 'item_description')) {
                    $table->text('item_description')->nullable();
                }
                if (! Schema::hasColumn('invoice', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->default(0.00);
                }
                if (! Schema::hasColumn('invoice', 'subtotal')) {
                    $table->decimal('subtotal', 10, 2)->default(0.00);
                }
                if (! Schema::hasColumn('invoice', 'tax_label')) {
                    $table->string('tax_label', 40)->nullable();
                }
                if (! Schema::hasColumn('invoice', 'tax_rate')) {
                    $table->decimal('tax_rate', 5, 2)->nullable();
                }
                if (! Schema::hasColumn('invoice', 'tax_amount')) {
                    $table->decimal('tax_amount', 10, 2)->default(0.00);
                }
                if (! Schema::hasColumn('invoice', 'payment_method')) {
                    $table->string('payment_method', 50)->nullable();
                }
                if (! Schema::hasColumn('invoice', 'payment_reference')) {
                    $table->string('payment_reference', 120)->nullable();
                }
                if (! Schema::hasColumn('invoice', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable();
                }
                if (! Schema::hasColumn('invoice', 'seller_snapshot')) {
                    $table->json('seller_snapshot')->nullable();
                }
                if (! Schema::hasColumn('invoice', 'buyer_snapshot')) {
                    $table->json('buyer_snapshot')->nullable();
                }
            });

            $colsToDrop = array_values(array_filter([
                'buyer_business_name',
                'seller_business_name',
            ], fn (string $col): bool => Schema::hasColumn('invoice', $col)));

            if (! empty($colsToDrop)) {
                Schema::table('invoice', function (Blueprint $table) use ($colsToDrop): void {
                    $table->dropColumn($colsToDrop);
                });
            }
        }
    }
};
