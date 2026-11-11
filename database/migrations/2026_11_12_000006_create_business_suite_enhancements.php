<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add status and facility tracking to waste_records
        Schema::table('waste_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('waste_records', 'status')) {
                $table->string('status', 30)->default('in_storage')->after('is_diverted');
            }
            if (! Schema::hasColumn('waste_records', 'facility_name')) {
                $table->string('facility_name', 150)->nullable()->after('status');
            }
            if (! Schema::hasColumn('waste_records', 'facility_location')) {
                $table->string('facility_location', 100)->nullable()->after('facility_name');
            }
            if (! Schema::hasColumn('waste_records', 'ai_recommendation')) {
                $table->text('ai_recommendation')->nullable()->after('facility_location');
            }
        });

        // 2. Add payment and invoice tracking to material_exchanges
        Schema::table('material_exchanges', function (Blueprint $table): void {
            if (! Schema::hasColumn('material_exchanges', 'invoice_number')) {
                $table->string('invoice_number', 50)->nullable()->after('id');
            }
            if (! Schema::hasColumn('material_exchanges', 'payment_status')) {
                $table->string('payment_status', 30)->default('pending_payment')->after('status');
            }
            if (! Schema::hasColumn('material_exchanges', 'payment_reference')) {
                $table->string('payment_reference', 100)->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('material_exchanges', 'payment_requested_at')) {
                $table->timestamp('payment_requested_at')->nullable()->after('payment_reference');
            }
            if (! Schema::hasColumn('material_exchanges', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_requested_at');
            }
        });

        // 3. Create marketplace reviews and feedback table
        if (! Schema::hasTable('marketplace_reviews')) {
            Schema::create('marketplace_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
                $table->foreignId('seller_business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('reviewer_business_id')->constrained('business')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(5);
                $table->text('comment');
                $table->string('exchange_type', 30)->default('buy');
                $table->timestamps();

                $table->index(['listing_id', 'rating']);
                $table->index(['seller_business_id']);
                $table->index(['reviewer_business_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_reviews');

        Schema::table('material_exchanges', function (Blueprint $table): void {
            $cols = ['invoice_number', 'payment_status', 'payment_reference', 'payment_requested_at', 'paid_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('material_exchanges', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('waste_records', function (Blueprint $table): void {
            $cols = ['status', 'facility_name', 'facility_location', 'ai_recommendation'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('waste_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
