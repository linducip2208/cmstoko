<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('requires_shipping')->default(true)->after('weight');
            $table->unsignedInteger('download_limit')->nullable()->after('requires_shipping'); // null = unlimited
            $table->unsignedInteger('download_expiry_days')->nullable()->after('download_limit');
        });

        // Downloadable assets — stored on the PRIVATE local disk (never web-accessible).
        Schema::create('product_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('label', 200)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });

        Schema::create('product_download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamp('downloaded_at')->useCurrent();

            $table->index(['order_item_id', 'downloaded_at']);
        });

        // Grouped products: parent merchandises children (each purchasable separately).
        Schema::create('grouped_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['parent_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grouped_products');
        Schema::dropIfExists('product_download_logs');
        Schema::dropIfExists('product_downloads');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['requires_shipping', 'download_limit', 'download_expiry_days']);
        });
    }
};
