<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id', 'variant_id'], 'inv_levels_wh_product_variant');
            $table->index('product_id');
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 40)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status', 20)->default('pending'); // pending|in_transit|received|cancelled
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        // Seed opening levels for the default warehouse from existing flat stock.
        $defaultWarehouseId = DB::table('warehouses')->where('is_default', true)->value('id');

        if ($defaultWarehouseId) {
            $now = now();

            $rows = [];
            foreach (DB::table('products')->get(['id', 'stock']) as $product) {
                $rows[] = [
                    'warehouse_id' => $defaultWarehouseId,
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'stock' => (int) $product->stock,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (DB::table('product_variants')->get(['id', 'product_id', 'stock']) as $variant) {
                $rows[] = [
                    'warehouse_id' => $defaultWarehouseId,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'stock' => (int) $variant->stock,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('inventory_levels')->insertOrIgnore($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('inventory_levels');
    }
};
