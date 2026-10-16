<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Edades (en meses) en las que se pide actualizar la foto.
     * Solo aplica a animales registrados con menos de 24 meses de edad.
     */
    private array $milestones = [6, 24, 48];

    private int $maxRegistrationAge = 24;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animal_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->date('taken_at')->nullable();
            $table->timestamps();
            $table->index(['animal_id', 'taken_at']);
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->date('next_photo_due_on')->nullable()->after('photo_path');
        });

        // Rellena el historial con la foto actual y calcula el próximo aviso de cada animal.
        DB::table('animals')->whereNotNull('photo_path')->get()->each(function ($animal) {
            $createdAt = $animal->created_at ? substr((string) $animal->created_at, 0, 10) : null;
            $takenAt = $createdAt;

            DB::table('animal_photos')->insert([
                'animal_id' => $animal->id,
                'user_id' => $animal->user_id,
                'path' => $animal->photo_path,
                'taken_at' => $takenAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('animals')->where('id', $animal->id)->update([
                'next_photo_due_on' => $this->nextPhotoDue($animal, $takenAt),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('next_photo_due_on');
        });

        Schema::dropIfExists('animal_photos');
    }

    private function nextPhotoDue(object $animal, ?string $takenAt): ?string
    {
        $birth = $this->birthDate($animal);
        $registeredOn = $animal->created_at ? substr((string) $animal->created_at, 0, 10) : null;

        if (! $birth || ! $registeredOn) {
            return null;
        }

        $registrationAge = $this->monthsBetween($birth, new DateTimeImmutable($registeredOn));

        if ($registrationAge >= $this->maxRegistrationAge) {
            return null;
        }

        $milestones = array_values(array_filter($this->milestones, fn (int $month): bool => $month > $registrationAge));
        $latestAge = $takenAt ? $this->monthsBetween($birth, new DateTimeImmutable($takenAt)) : null;

        foreach ($milestones as $milestone) {
            if ($latestAge === null || $latestAge < $milestone) {
                return $birth->modify("+{$milestone} months")->format('Y-m-d');
            }
        }

        return null;
    }

    private function birthDate(object $animal): ?DateTimeImmutable
    {
        if (! empty($animal->birth_date)) {
            return new DateTimeImmutable(substr((string) $animal->birth_date, 0, 10));
        }

        if (! empty($animal->purchase_date) && $animal->age_at_purchase_months !== null) {
            return (new DateTimeImmutable(substr((string) $animal->purchase_date, 0, 10)))
                ->modify('-'.(int) $animal->age_at_purchase_months.' months');
        }

        return null;
    }

    private function monthsBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $diff = $from->diff($to);

        return ($diff->y * 12) + $diff->m;
    }
};
