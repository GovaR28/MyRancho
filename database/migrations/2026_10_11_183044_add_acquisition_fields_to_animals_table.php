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
        Schema::table('animals', function (Blueprint $table) {
            $table->enum('acquisition_type', ['born', 'purchased'])->nullable()->after('sex');
            $table->enum('reproduction_method', ['natural', 'insemination', 'embryo_transfer'])->nullable()->after('origin');
            $table->decimal('purchase_price', 12, 2)->nullable()->after('reproduction_method');
            $table->date('purchase_date')->nullable()->after('purchase_price');
            $table->string('seller_name')->nullable()->after('purchase_date');
            $table->integer('age_at_purchase_months')->nullable()->after('seller_name');
            $table->boolean('came_with_mother')->nullable()->default(false)->after('age_at_purchase_months');
            $table->string('purchased_breed')->nullable()->after('came_with_mother');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn([
                'acquisition_type',
                'reproduction_method',
                'purchase_price',
                'purchase_date',
                'seller_name',
                'age_at_purchase_months',
                'came_with_mother',
                'purchased_breed',
            ]);
        });
    }
};
