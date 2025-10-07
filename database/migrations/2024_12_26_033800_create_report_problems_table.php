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
        Schema::create('report_problems', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();

            $table->string('entity_type'); // video, story, user gibi
            $table->string('entity_id'); // Mongo ObjectId ya da UUID ya da id

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->smallInteger('status_id');

            $table->smallInteger('report_problem_category_id')->nullable();

            $table->text('message')->nullable();

            $table->uuid('admin_id')->nullable();
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');

            $table->timestampTz('read_at')->nullable();
            $table->text('admin_response')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_problems');
    }
};
