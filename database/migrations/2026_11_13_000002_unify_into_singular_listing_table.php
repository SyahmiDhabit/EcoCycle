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

        $createView = function (string $viewName, string $sourceTable, ?string $where = null) use ($isPgsql): void {
            $query = "SELECT * FROM {$sourceTable}" . ($where ? " WHERE {$where}" : "");
            if ($isPgsql) {
                DB::statement("CREATE OR REPLACE VIEW {$viewName} AS {$query}");
            } else {
                DB::statement("DROP VIEW IF EXISTS {$viewName}");
                DB::statement("CREATE VIEW {$viewName} AS {$query}");
            }
        };

        if ($isPgsql) {
            // 1. Prepare marketplace_listings with missing inventory fields before rename
            if (Schema::hasTable('marketplace_listings')) {
                Schema::table('marketplace_listings', function (Blueprint $table): void {
                    if (! Schema::hasColumn('marketplace_listings', 'sku')) {
                        $table->string('sku', 100)->nullable();
                    }
                    if (! Schema::hasColumn('marketplace_listings', 'minimum_threshold')) {
                        $table->decimal('minimum_threshold', 12, 2)->nullable();
                    }
                    if (! Schema::hasColumn('marketplace_listings', 'notes')) {
                        $table->text('notes')->nullable();
                    }
                    if (! Schema::hasColumn('marketplace_listings', 'name')) {
                        $table->string('name', 255)->nullable();
                    }
                });

                DB::statement('UPDATE marketplace_listings SET name = title WHERE name IS NULL');

                // Copy any inventory_items not yet linked to marketplace_listings
                if (Schema::hasTable('inventory_items')) {
                    DB::statement("
                        INSERT INTO marketplace_listings (
                            business_id, title, name, description, sku, category, quantity, unit,
                            price, is_free, listing_type, reusability, condition, minimum_threshold,
                            status, notes, image_path, image_1, image_2, image_3, image_4, image_5,
                            images, created_at, updated_at
                        )
                        SELECT
                            business_id, name, name, description, sku, category, quantity, unit,
                            price, (CASE WHEN listing_type = 'free' THEN true ELSE false END),
                            COALESCE(listing_type, 'buy'), COALESCE(reusability, 'reusable'),
                            COALESCE(condition, 'Used - Good'), minimum_threshold, status, notes,
                            image_path, image_1, image_2, image_3, image_4, image_5, images,
                            created_at, updated_at
                        FROM inventory_items
                        WHERE id NOT IN (
                            SELECT inventory_item_id FROM marketplace_listings WHERE inventory_item_id IS NOT NULL
                        )
                    ");
                }
            }

            DB::statement('ALTER TABLE marketplace_listings DROP CONSTRAINT IF EXISTS marketplace_listings_inventory_item_id_foreign');
            DB::statement('ALTER TABLE waste_records DROP CONSTRAINT IF EXISTS waste_records_inventory_item_id_foreign');
            DB::statement('ALTER TABLE inventory_transactions DROP CONSTRAINT IF EXISTS inventory_transactions_inventory_item_id_foreign');
            DB::statement('DROP TABLE IF EXISTS inventory_items CASCADE');

            // Rename marketplace_listings to singular 'listing'
            if (Schema::hasTable('marketplace_listings') && ! Schema::hasTable('listing')) {
                Schema::rename('marketplace_listings', 'listing');
            }

            DB::statement('ALTER TABLE listing ALTER COLUMN description DROP NOT NULL');
            DB::statement('ALTER TABLE listing ALTER COLUMN location_city DROP NOT NULL');
            DB::statement('ALTER TABLE listing ALTER COLUMN location_state DROP NOT NULL');

            if (Schema::hasTable('waste_records')) {
                DB::statement('ALTER TABLE waste_records ADD CONSTRAINT waste_records_inventory_item_id_foreign FOREIGN KEY (inventory_item_id) REFERENCES listing(id) ON DELETE SET NULL');
            }
            if (Schema::hasTable('inventory_transactions')) {
                DB::statement('ALTER TABLE inventory_transactions ADD CONSTRAINT inventory_transactions_inventory_item_id_foreign FOREIGN KEY (inventory_item_id) REFERENCES listing(id) ON DELETE CASCADE');
            }
        } else {
            // For SQLite (e.g. testing), recreate tables with clean foreign keys referencing singular 'listing'
            Schema::dropIfExists('marketplace_listings');
            Schema::dropIfExists('inventory_items');
            Schema::dropIfExists('listing');

            Schema::create('listing', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->unsignedBigInteger('inventory_item_id')->nullable();
                $table->string('title')->nullable();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->string('sku', 100)->nullable();
                $table->string('category', 60);
                $table->decimal('quantity', 12, 2)->default(0);
                $table->string('unit', 20)->default('pcs');
                $table->decimal('price', 10, 2)->nullable()->default(0);
                $table->boolean('is_free')->default(false);
                $table->string('listing_type', 30)->default('buy');
                $table->string('reusability', 30)->default('reusable');
                $table->string('condition', 40)->default('Used - Good');
                $table->decimal('minimum_threshold', 12, 2)->nullable();
                $table->string('location_city', 100)->nullable();
                $table->string('location_state', 40)->nullable();
                $table->string('image_path')->nullable();
                $table->string('image_1')->nullable();
                $table->string('image_2')->nullable();
                $table->string('image_3')->nullable();
                $table->string('image_4')->nullable();
                $table->string('image_5')->nullable();
                $table->json('images')->nullable();
                $table->string('status', 30)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['status', 'category']);
                $table->index(['business_id', 'status']);
            });

            Schema::dropIfExists('inventory_transactions');
            Schema::dropIfExists('inventory_transaction');
            Schema::create('inventory_transaction', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('listing')->cascadeOnDelete();
                $table->string('transaction_type', 30);
                $table->decimal('quantity_change', 12, 2);
                $table->decimal('quantity_after', 12, 2);
                $table->string('notes')->nullable();
                $table->timestamps();
            });

            Schema::dropIfExists('waste_records');
            Schema::dropIfExists('waste_record');
            Schema::create('waste_record', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->nullable()->constrained('listing')->nullOnDelete();
                $table->string('material_name');
                $table->string('waste_type', 60);
                $table->decimal('quantity', 12, 2);
                $table->string('unit', 20)->default('kg');
                $table->string('disposal_method', 60);
                $table->boolean('is_diverted')->default(true);
                $table->string('status', 30)->default('in_storage');
                $table->string('facility_name')->nullable();
                $table->string('facility_location')->nullable();
                $table->text('ai_recommendation')->nullable();
                $table->decimal('cost_or_savings', 10, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->date('recorded_at');
                $table->timestamps();
            });

            Schema::dropIfExists('material_exchanges');
            Schema::dropIfExists('material_exchange');
            Schema::create('material_exchange', function (Blueprint $table): void {
                $table->id();
                $table->string('invoice_number', 50)->nullable();
                $table->foreignId('listing_id')->constrained('listing')->cascadeOnDelete();
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

            Schema::dropIfExists('marketplace_reviews');
            Schema::dropIfExists('marketplace_review');
            Schema::create('marketplace_review', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('listing_id')->constrained('listing')->cascadeOnDelete();
                $table->foreignId('seller_business_id')->constrained('business')->cascadeOnDelete();
                $table->foreignId('reviewer_business_id')->constrained('business')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(5);
                $table->text('comment');
                $table->string('exchange_type', 30)->default('buy');
                $table->timestamps();
            });
        }

        // Create backwards-compatible views so queries referencing old plural names continue to work
        $createView('inventory_items', 'listing');
        $createView('marketplace_listings', 'listing', "reusability != 'not_reusable' OR reusability IS NULL");
        $createView('waste_records', 'waste_record');
        $createView('inventory_transactions', 'inventory_transaction');
        $createView('material_exchanges', 'material_exchange');
        $createView('marketplace_reviews', 'marketplace_review');

        // Rename remaining entities to singular (no 's')
        $renames = [
            'material_exchanges' => 'material_exchange',
            'marketplace_reviews' => 'marketplace_review',
            'waste_records' => 'waste_record',
            'inventory_transactions' => 'inventory_transaction',
            'business_notifications' => 'business_notification',
            'admin_notifications' => 'admin_notification',
        ];

        foreach ($renames as $plural => $singular) {
            if (Schema::hasTable($plural) && ! Schema::hasTable($singular)) {
                Schema::rename($plural, $singular);
            }
            if (Schema::hasTable($singular)) {
                $createView($plural, $singular);
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $renames = [
            'material_exchange' => 'material_exchanges',
            'marketplace_review' => 'marketplace_reviews',
            'waste_record' => 'waste_records',
            'inventory_transaction' => 'inventory_transactions',
            'business_notification' => 'business_notifications',
            'admin_notification' => 'admin_notifications',
        ];

        foreach ($renames as $singular => $plural) {
            if (Schema::hasTable($singular) && ! Schema::hasTable($plural)) {
                DB::statement("DROP VIEW IF EXISTS {$plural}");
                Schema::rename($singular, $plural);
            }
        }

        if (Schema::hasTable('listing') && ! Schema::hasTable('marketplace_listings')) {
            DB::statement('DROP VIEW IF EXISTS marketplace_listings');
            DB::statement('DROP VIEW IF EXISTS inventory_items');
            Schema::rename('listing', 'marketplace_listings');
        }
    }
};
