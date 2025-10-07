<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 255)->index();
            $table->json('payload'); // JSON verisi olarak saklanmalı
            $table->unsignedTinyInteger('attempts'); // PostgreSQL tinyInteger desteklemez
            $table->unsignedBigInteger('reserved_at')->nullable();
            $table->unsignedBigInteger('available_at');
            $table->unsignedBigInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID olarak düzeltilmiş
            $table->string('name', 255);
            $table->unsignedBigInteger('total_jobs');
            $table->unsignedBigInteger('pending_jobs');
            $table->unsignedBigInteger('failed_jobs');
            $table->json('failed_job_ids'); // JSON olarak saklanmalı
            $table->text('options')->nullable();
            $table->unsignedBigInteger('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_at');
            $table->unsignedBigInteger('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // UUID olarak düzeltilmiş
            $table->string('connection', 255);
            $table->string('queue', 255);
            $table->json('payload'); // JSON olarak saklanmalı
            $table->json('exception'); // JSON olarak saklanmalı
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
