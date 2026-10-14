<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vaccination extends Model
{
    /**
     * Alcances posibles de una vacunación: todo el ganado, un lote,
     * un grupo seleccionado o una sola vaca.
     */
    public const SCOPES = [
        'todo' => 'Todo el ganado',
        'lote' => 'Un lote',
        'grupo' => 'Un grupo de vacas',
        'individual' => 'Una sola vaca',
    ];

    protected $fillable = [
        'vaccination_date',
        'vaccine_name',
        'vaccine_class',
        'vaccinator',
        'scope',
        'lote_id',
        'notes',
    ];

    protected function casts(): array
    {
        return ['vaccination_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function animals(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'animal_vaccination')->withTimestamps();
    }

    public function scopeLabel(): string
    {
        return self::SCOPES[$this->scope] ?? $this->scope;
    }
}
