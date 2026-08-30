<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            // action: percent | fixed | free_shipping
            $table->string('action_type', 30)->default('percent');
            $table->unsignedInteger('action_value')->default(0); // percent 1-100 or fixed IDR
            // targeting
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->json('conditions')->nullable(); // {min_subtotal, max_subtotal, product_ids, category_ids, brand_ids, quantity_min}
            $table->unsignedInteger('priority')->default(0); // higher evaluated first
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->json('applied_rules')->nullable()->after('coupon_code');
            $table->unsignedInteger('rule_discount')->default(0)->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['applied_rules', 'rule_discount']);
        });

        Schema::dropIfExists('cart_rules');
    }
};
