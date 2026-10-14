<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fincas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->decimal('area_hectares', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finca_id')->constrained('fincas')->restrictOnDelete();
            $table->foreignId('mother_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('father_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->string('ear_tag');
            $table->string('name')->nullable();
            $table->string('sex', 10);
            $table->string('breed')->nullable();
            $table->string('color')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('origin', 20)->nullable();
            $table->string('record_type', 20)->default('comercial');
            $table->string('registration_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'ear_tag']);
            $table->index(['finca_id', 'sex']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
        Schema::dropIfExists('fincas');
    }
};
