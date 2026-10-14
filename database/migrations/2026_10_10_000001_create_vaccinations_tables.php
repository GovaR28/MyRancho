<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('vaccination_date');
            $table->string('vaccine_name', 120);
            $table->string('vaccine_class', 60);
            $table->string('vaccinator', 120);
            $table->string('scope', 20);
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'vaccination_date']);
        });

        Schema::create('animal_vaccination', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaccination_id')->constrained('vaccinations')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['vaccination_id', 'animal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_vaccination');
        Schema::dropIfExists('vaccinations');
    }
};
