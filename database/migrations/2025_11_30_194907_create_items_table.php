<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->integer('api_id')->unique();
            $table->string('name');
            $table->integer('cost')->nullable();
            $table->integer('fling_power')->nullable();
            $table->string('fling_effect')->nullable();
            $table->string('category')->nullable();
            $table->text('effect')->nullable();
            $table->text('short_effect')->nullable();
            $table->text('flavor_text')->nullable();
            $table->string('sprite')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('api_id');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
