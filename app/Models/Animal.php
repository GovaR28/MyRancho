<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Animal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Edades (en meses) en las que se pide actualizar la foto del animal.
     */
    public const PHOTO_MILESTONES = [6, 24, 48];

    /**
     * Solo se hace seguimiento de la foto a los animales registrados antes de esta edad (meses).
     */
    public const PHOTO_MAX_REGISTRATION_AGE = 24;

    protected $appends = ['photo_url', 'photo_needs_update', 'photo_due_stage'];

    protected $fillable = [
        'finca_id',
        'propietario_id',
        'mother_id',
        'mother_unknown',
        'father_id',
        'father_unknown',
        'ear_tag',
        'name',
        'sex',
        'acquisition_type',
        'purchase_source',
        'breed',
        'color',
        'birth_date',
        'birth_weight_kg',
        'origin',
        'reproduction_method',
        'reproduction_method_other',
        'purchase_price',
        'purchase_weight_kg',
        'purchase_date',
        'seller_name',
        'age_at_purchase_months',
        'came_with_mother',
        'purchased_breed',
        'record_type',
        'registration_number',
        'exit_date',
        'exit_reason',
        'exit_notes',
        'photo_path',
        'next_photo_due_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'purchase_date' => 'date',
            'exit_date' => 'date',
            'next_photo_due_on' => 'date',
            'purchase_price' => 'decimal:2',
            'purchase_weight_kg' => 'decimal:2',
            'birth_weight_kg' => 'decimal:2',
            'mother_unknown' => 'boolean',
            'father_unknown' => 'boolean',
            'came_with_mother' => 'boolean',
        ];
    }

    /**
     * Devuelve el siguiente número de arete consecutivo para la cuenta.
     *
     * Se calcula sobre el mayor número ya usado (incluyendo vendidos y animales
     * dados de baja), así un arete nunca se reutiliza. Los aretes no numéricos
     * (p. ej. "A-01") se ignoran. Se muestra con ceros a la izquierda (001, 002…).
     */
    public static function nextEarTag(User $user, int $offset = 0): string
    {
        $highest = $user->animals()
            ->withTrashed()
            ->pluck('ear_tag')
            ->reduce(function (int $carry, $tag): int {
                $digits = ltrim((string) $tag, '0');

                if ($digits !== '' && ctype_digit($digits)) {
                    return max($carry, (int) $digits);
                }

                return $carry;
            }, 0);

        return str_pad((string) ($highest + 1 + $offset), 3, '0', STR_PAD_LEFT);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path ? asset('storage/'.$this->photo_path) : null);
    }

    protected static function booted(): void
    {
        // Mantiene el próximo aviso de foto calculado ante altas y ediciones.
        static::created(fn (Animal $animal) => $animal->recalculateNextPhotoDue());
        static::updated(fn (Animal $animal) => $animal->recalculateNextPhotoDue());
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AnimalPhoto::class)
            ->orderByDesc('taken_at')
            ->orderByDesc('id');
    }

    /**
     * Fecha de nacimiento efectiva: la registrada o la estimada a partir de la compra.
     */
    public function effectiveBirthDate(): ?Carbon
    {
        if ($this->birth_date) {
            return $this->birth_date->copy();
        }

        if ($this->purchase_date && $this->age_at_purchase_months !== null) {
            return $this->purchase_date->copy()->subMonths((int) $this->age_at_purchase_months);
        }

        return null;
    }

    /**
     * Edad en meses del animal en una fecha concreta.
     */
    public function ageInMonthsAt(\DateTimeInterface|string|null $date): ?int
    {
        $birth = $this->effectiveBirthDate();

        if (! $birth || ! $date) {
            return null;
        }

        $target = $date instanceof \DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date);

        if ($target->lessThan($birth)) {
            return 0;
        }

        $diff = $birth->diff($target);

        return ($diff->y * 12) + $diff->m;
    }

    /**
     * Edad con la que el animal entró al sistema (meses).
     */
    public function registrationAgeMonths(): ?int
    {
        if (! $this->created_at) {
            return null;
        }

        return $this->ageInMonthsAt($this->created_at);
    }

    /**
     * Etapas de foto que aplican a este animal: solo si fue registrado joven (< 2 años)
     * y solo los hitos posteriores a la edad de registro.
     *
     * @return array<int, int>
     */
    public function photoMilestones(): array
    {
        $registrationAge = $this->registrationAgeMonths();

        if ($registrationAge === null || $registrationAge >= self::PHOTO_MAX_REGISTRATION_AGE) {
            return [];
        }

        return array_values(array_filter(
            self::PHOTO_MILESTONES,
            fn (int $month): bool => $month > $registrationAge,
        ));
    }

    public static function formatAgeMonths(?int $months): ?string
    {
        if ($months === null) {
            return null;
        }

        if ($months < 1) {
            return 'Recién nacido';
        }

        if ($months < 12) {
            return $months === 1 ? '1 mes' : "{$months} meses";
        }

        $years = intdiv($months, 12);
        $remaining = $months % 12;
        $label = $years === 1 ? '1 año' : "{$years} años";

        return $remaining ? "{$label} {$remaining} m" : $label;
    }

    /**
     * Recalcula la próxima fecha en la que se pedirá actualizar la foto.
     */
    public function recalculateNextPhotoDue(): void
    {
        $milestones = $this->photoMilestones();
        $birth = $this->effectiveBirthDate();

        if (empty($milestones) || ! $birth) {
            $this->forceFill(['next_photo_due_on' => null])->saveQuietly();

            return;
        }

        $latestAge = $this->photos()
            ->get(['id', 'taken_at'])
            ->max(fn (AnimalPhoto $photo): ?int => $this->ageInMonthsAt($photo->taken_at));

        $due = null;

        foreach ($milestones as $milestone) {
            if ($latestAge === null || $latestAge < $milestone) {
                $due = $birth->copy()->addMonths($milestone);
                break;
            }
        }

        $this->forceFill(['next_photo_due_on' => $due?->toDateString()])->saveQuietly();
    }

    /**
     * Agrega una foto al historial y la deja como la foto actual.
     */
    public function addPhoto(string $path, \DateTimeInterface|string|null $takenAt = null): AnimalPhoto
    {
        $takenAt = $takenAt
            ? ($takenAt instanceof \DateTimeInterface ? Carbon::instance($takenAt) : Carbon::parse($takenAt))
            : now();

        $photo = $this->photos()->create([
            'user_id' => $this->user_id,
            'path' => $path,
            'taken_at' => $takenAt->toDateString(),
        ]);

        $this->forceFill(['photo_path' => $path])->saveQuietly();
        $this->recalculateNextPhotoDue();

        return $photo;
    }

    protected function photoNeedsUpdate(): Attribute
    {
        return Attribute::get(fn (): bool => $this->next_photo_due_on !== null
            && $this->next_photo_due_on->lessThanOrEqualTo(now()));
    }

    protected function photoDueStage(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->next_photo_due_on) {
                return null;
            }

            return self::formatAgeMonths($this->ageInMonthsAt($this->next_photo_due_on));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class)->withTrashed();
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class)->withTrashed();
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id')->withTrashed();
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(self::class, 'father_id')->withTrashed();
    }

    public function lotes(): BelongsToMany
    {
        return $this->belongsToMany(Lote::class, 'lote_animal')->withTimestamps();
    }

    public function ventas(): BelongsToMany
    {
        return $this->belongsToMany(Venta::class, 'animal_venta')->withTimestamps();
    }

    public function vaccinations(): BelongsToMany
    {
        return $this->belongsToMany(Vaccination::class, 'animal_vaccination')->withTimestamps();
    }
}
