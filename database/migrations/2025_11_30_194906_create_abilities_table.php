<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abilities', function (Blueprint $table) {
            $table->id();
            $table->integer('api_id')->unique();
            $table->string('name');
            $table->text('effect')->nullable();
            $table->text('short_effect')->nullable();
            $table->boolean('is_main_series')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('api_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abilities');
    }
};
