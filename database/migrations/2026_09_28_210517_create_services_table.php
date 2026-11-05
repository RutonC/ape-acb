<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->index();
            $table->string('slug');
            $table->json('title');
            $table->json('tagline')->nullable();
            $table->json('summary')->nullable();
            $table->json('intro')->nullable();
            $table->json('details')->nullable();
            $table->json('audience')->nullable();
            $table->json('timeline')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('curator')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['kind', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
