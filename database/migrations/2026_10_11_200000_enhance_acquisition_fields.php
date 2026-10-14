<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('purchase_source', 20)->nullable()->after('acquisition_type');
            $table->boolean('mother_unknown')->default(false)->after('mother_id');
            $table->boolean('father_unknown')->default(false)->after('father_id');
            $table->decimal('birth_weight_kg', 8, 2)->nullable()->after('birth_date');
            $table->decimal('purchase_weight_kg', 8, 2)->nullable()->after('purchase_price');
            $table->string('reproduction_method_other', 120)->nullable()->after('reproduction_method');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE animals MODIFY reproduction_method ENUM('natural','insemination','embryo_transfer','other') NULL DEFAULT NULL");
        } else {
            // En SQLite los enums se crean como varchar con CHECK; se recrea la columna como texto libre.
            Schema::table('animals', function (Blueprint $table) {
                $table->dropColumn('reproduction_method');
            });
            Schema::table('animals', function (Blueprint $table) {
                $table->string('reproduction_method', 30)->nullable()->after('reproduction_method_other');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE animals MODIFY reproduction_method ENUM('natural','insemination','embryo_transfer') NULL DEFAULT NULL");
        }

        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_source',
                'mother_unknown',
                'father_unknown',
                'birth_weight_kg',
                'purchase_weight_kg',
                'reproduction_method_other',
            ]);
        });
    }
};
