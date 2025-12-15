<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_chains', function (Blueprint $table) {
            $table->id();
            $table->integer('api_id')->unique();
            $table->string('baby_trigger_item')->nullable();
            $table->timestamps();

            $table->index('api_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_chains');
    }
};
