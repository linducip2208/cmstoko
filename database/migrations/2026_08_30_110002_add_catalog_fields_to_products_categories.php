<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            $table->string('icon')->nullable()->after('image');
            $table->string('cover_image')->nullable()->after('icon');
            $table->string('short_description', 300)->nullable()->after('description');
            $table->json('seo')->nullable()->after('description');
            $table->index('parent_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->string('type')->default('simple')->after('brand_id'); // simple|configurable
            $table->string('short_description', 500)->nullable()->after('description');
            $table->json('seo')->nullable()->after('is_featured');
            $table->json('attribute_values')->nullable()->after('seo'); // non-variant attributes, keyed by attribute slug
            $table->timestamp('published_at')->nullable()->after('attribute_values');
            $table->index('brand_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['type', 'short_description', 'seo', 'attribute_values', 'published_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['icon', 'cover_image', 'short_description', 'seo']);
        });
    }
};
