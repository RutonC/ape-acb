<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('contract_type')->nullable();
            $table->json('location')->nullable();
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->json('requirements')->nullable();
            $table->unsignedSmallInteger('positions')->nullable();
            $table->string('experience')->nullable();
            $table->string('salary')->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('curator')->nullOnDelete();
            $table->boolean('is_open')->default(true);
            $table->date('posted_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_openings');
        Schema::dropIfExists('job_categories');
    }
};
