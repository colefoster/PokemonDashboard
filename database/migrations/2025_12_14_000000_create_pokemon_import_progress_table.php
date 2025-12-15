<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemon_import_progress', function (Blueprint $table) {
            $table->id();
            $table->string('import_id')->unique();
            $table->string('status')->default('running'); // running, completed, failed
            $table->string('current_step')->nullable();
            $table->integer('current_step_index')->default(0);
            $table->integer('total_steps')->default(7);
            $table->integer('current_step_processed')->default(0);
            $table->integer('current_step_total')->nullable();
            $table->json('step_details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('import_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon_import_progress');
    }
};
