<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('lote_animal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->timestamps();
            $table->unique('animal_id');
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->date('sale_date');
            $table->decimal('total_price', 12, 2);
            $table->string('buyer_name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('animal_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->timestamps();
            $table->unique('animal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_venta');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('lote_animal');
        Schema::dropIfExists('lotes');
    }
};
