<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item')) {
            Schema::table('item', function (Blueprint $table): void {
                if (! Schema::hasColumn('item', 'pickup_address')) {
                    $table->string('pickup_address', 255)->nullable();
                }
            });

            // Safely normalize any obsolete listing_type values (borrow, lend, exchange, barter, swap)
            // without deleting existing inventory or marketplace records.
            if (Schema::hasColumn('item', 'listing_type')) {
                DB::table('item')
                    ->whereNotIn('listing_type', ['buy', 'free'])
                    ->where('is_free', true)
                    ->update(['listing_type' => 'free']);

                DB::table('item')
                    ->whereNotIn('listing_type', ['buy', 'free'])
                    ->update(['listing_type' => 'buy']);
            }
        }

        if (Schema::hasTable('material_exchange')) {
            if (Schema::hasColumn('material_exchange', 'listing_type')) {
                DB::table('material_exchange')
                    ->whereNotIn('listing_type', ['buy', 'free'])
                    ->where('total_price', '<=', 0)
                    ->update(['listing_type' => 'free']);

                DB::table('material_exchange')
                    ->whereNotIn('listing_type', ['buy', 'free'])
                    ->update(['listing_type' => 'buy']);
            }

            if (Schema::hasColumn('material_exchange', 'payment_status')) {
                DB::table('material_exchange')
                    ->where('payment_status', 'borrow_pending')
                    ->update(['payment_status' => 'pending_payment']);
            }
        }

        if (Schema::hasTable('invoice')) {
            Schema::table('invoice', function (Blueprint $table): void {
                if (! Schema::hasColumn('invoice', 'pickup_address')) {
                    $table->string('pickup_address', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoice') && Schema::hasColumn('invoice', 'pickup_address')) {
            Schema::table('invoice', function (Blueprint $table): void {
                $table->dropColumn('pickup_address');
            });
        }

        if (Schema::hasTable('item') && Schema::hasColumn('item', 'pickup_address')) {
            Schema::table('item', function (Blueprint $table): void {
                $table->dropColumn('pickup_address');
            });
        }
    }
};
