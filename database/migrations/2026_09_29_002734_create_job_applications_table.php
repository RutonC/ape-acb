<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('birth_date');
            $table->string('gender', 20);
            $table->string('document_type', 30)->nullable();
            $table->text('document_number')->nullable();
            $table->string('qualifications')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('address');
            $table->string('email');
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->text('message')->nullable();
            $table->string('cv_path');
            $table->string('cv_original_name');
            $table->string('locale', 5)->default('pt');
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('company_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_registrations');
        Schema::dropIfExists('job_applications');
    }
};
