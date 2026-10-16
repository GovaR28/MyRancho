<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Animal extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['photo_url'];

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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'purchase_date' => 'date',
            'exit_date' => 'date',
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
