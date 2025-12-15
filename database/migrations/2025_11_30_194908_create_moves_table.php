<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->integer('api_id')->unique();
            $table->string('name');
            $table->integer('power')->nullable();
            $table->integer('pp')->nullable();
            $table->integer('accuracy')->nullable();
            $table->integer('priority')->nullable();
            $table->foreignId('type_id')->nullable()->constrained('types')->nullOnDelete();
            $table->string('damage_class')->nullable();
            $table->integer('effect_chance')->nullable();
            $table->string('contest_type')->nullable();
            $table->string('generation')->nullable();
            $table->text('effect')->nullable();
            $table->text('short_effect')->nullable();
            $table->text('flavor_text')->nullable();
            $table->string('target')->nullable();
            $table->string('ailment')->nullable();
            $table->string('meta_category')->nullable();
            $table->integer('min_hits')->nullable();
            $table->integer('max_hits')->nullable();
            $table->integer('min_turns')->nullable();
            $table->integer('max_turns')->nullable();
            $table->integer('drain')->nullable();
            $table->integer('healing')->nullable();
            $table->integer('crit_rate')->nullable();
            $table->integer('ailment_chance')->nullable();
            $table->integer('flinch_chance')->nullable();
            $table->integer('stat_chance')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('api_id');
            $table->index('damage_class');
            $table->index('generation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moves');
    }
};
