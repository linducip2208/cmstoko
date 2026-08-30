<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Zone = province_id/city_id nullables (null = wildcard / nationwide).
        // Rate stored in basis points: 11% = 1100 (integer math only, no floats).
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_class_id')->constrained('tax_classes')->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedInteger('rate_bp'); // basis points
            $table->string('type', 20)->default('exclusive'); // exclusive | inclusive
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->boolean('applies_to_shipping')->default(false);
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tax_class_id', 'is_active', 'priority']);
            $table->index(['province_id', 'city_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('tax_class_id')->nullable()->after('published_at')->constrained('tax_classes')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('tax_amount')->default(0)->after('rule_discount');
            $table->json('tax_snapshot')->nullable()->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'tax_snapshot']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_class_id');
        });

        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_classes');
    }
};
