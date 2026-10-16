<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\User;

class AlertService
{
    /**
     * Tipos de aviso disponibles.
     */
    public const PHOTO = 'photo';

    /**
     * Máximo de avisos que se envían al cliente (el resto se resume en el contador).
     */
    private const LIMIT = 25;

    /**
     * Avisos pendientes del usuario, ya sin los que ocultó.
     *
     * @return array{count: int, items: array<int, array<string, mixed>>, truncated: bool}
     */
    public function pending(User $user): array
    {
        $items = $this->photoAlerts($user);
        $count = count($items);
        $visible = array_slice($items, 0, self::LIMIT);

        return [
            'count' => $count,
            'items' => $visible,
            'truncated' => $count > count($visible),
        ];
    }

    /**
     * Todas las claves de avisos pendientes (sin el tope de visibles).
     *
     * @return array<int, string>
     */
    public function pendingKeys(User $user): array
    {
        return array_map(fn (array $item): string => $item['key'], $this->photoAlerts($user));
    }

    public function dismiss(User $user, string $key): void
    {
        $user->alertDismissals()->firstOrCreate(['alert_key' => $key]);
    }

    public function dismissAll(User $user): void
    {
        foreach ($this->pendingKeys($user) as $key) {
            $this->dismiss($user, $key);
        }
    }

    /**
     * Avisos de foto: animales que ya cumplieron una etapa sin foto reciente.
     *
     * @return array<int, array<string, mixed>>
     */
    private function photoAlerts(User $user): array
    {
        $dismissed = array_flip($user->alertDismissals()->pluck('alert_key')->all());

        $animals = $user->animals()
            ->whereNotNull('next_photo_due_on')
            ->where('next_photo_due_on', '<=', now()->toDateString())
            ->orderBy('next_photo_due_on')
            ->orderBy('id')
            ->get(['id', 'ear_tag', 'name', 'next_photo_due_on']);

        $alerts = [];

        foreach ($animals as $animal) {
            $key = self::photoKey($animal);

            if (isset($dismissed[$key])) {
                continue;
            }

            $alerts[] = $this->photoAlert($animal, $key);
        }

        return $alerts;
    }

    public static function photoKey(Animal $animal): string
    {
        return self::PHOTO.':'.$animal->id.':'.optional($animal->next_photo_due_on)->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function photoAlert(Animal $animal, string $key): array
    {
        $stage = Animal::formatAgeMonths($animal->ageInMonthsAt($animal->next_photo_due_on));
        $label = $animal->name ?: 'arete '.$animal->ear_tag;

        return [
            'key' => $key,
            'type' => self::PHOTO,
            'title' => 'Actualiza la foto de '.$label,
            'message' => $stage
                ? "Cumplió {$stage} y la foto sigue siendo la de antes."
                : 'Conviene subir una foto reciente.',
            'url' => '/animales?foto=pendiente&q='.urlencode((string) $animal->ear_tag),
            'date' => optional($animal->next_photo_due_on)->toDateString(),
        ];
    }
}
