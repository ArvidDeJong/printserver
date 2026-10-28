<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Which administration the job belongs to, so a box of one branch
            // never picks up the work of another. Null when there is only one.
            $table->string('administration', 50)->nullable();
            // The key of the printer, which is also the name of the queue on the box.
            $table->string('printer', 50);
            $table->string('title')->nullable();
            $table->string('format', 10)->default('pdf');
            // Relative path on the configured disk; the content stays out of the database.
            $table->string('path');
            $table->string('status', 20)->default('pending');
            // Name of the box that claimed the job, taken from its token.
            $table->string('claimed_by', 50)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['administration', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
