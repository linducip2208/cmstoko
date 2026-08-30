<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 255); // stored (safe) name
            $table->string('original_name', 255);
            $table->string('path', 500);
            $table->string('disk', 50)->default('public');
            $table->string('mime', 100);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('title', 200)->nullable();
            $table->string('alt', 300)->nullable();
            $table->string('caption', 500)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('mime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
