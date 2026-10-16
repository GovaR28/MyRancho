<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private function makeAnimal(User $user, array $overrides = []): Animal
    {
        $finca = $user->fincas()->first() ?? $user->fincas()->create(['name' => 'La Loma']);

        return $user->animals()->create(array_merge([
            'finca_id' => $finca->id,
            'ear_tag' => '001',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ], $overrides));
    }

    public function test_photo_alerts_appear_for_animals_that_are_due(): void
    {
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        Carbon::setTestNow('2026-08-01');

        $this->actingAs($user)->getJson('/alertas')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.type', 'photo')
            ->assertJsonPath('items.0.date', '2026-07-01');
    }

    public function test_an_alert_disappears_once_the_photo_is_updated(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $animal = $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        Carbon::setTestNow('2026-08-01');

        $this->actingAs($user)->getJson('/alertas')->assertJsonPath('count', 1);

        // Una foto tomada a los 7 meses cubre el hito de los 6 meses.
        $this->actingAs($user)->post("/animales/{$animal->id}/fotos", [
            'photo' => UploadedFile::fake()->create('cabra.jpg', 1, 'image/jpeg'),
            'taken_at' => '2026-08-01',
        ])->assertRedirect();

        $this->actingAs($user)->getJson('/alertas')->assertJsonPath('count', 0);
    }

    public function test_an_alert_can_be_dismissed(): void
    {
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $animal = $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        Carbon::setTestNow('2026-08-01');

        $key = AlertService::photoKey($animal->fresh());

        $this->actingAs($user)->getJson('/alertas')->assertJsonPath('count', 1);

        $this->actingAs($user)->postJson('/alertas/ocultar', ['key' => $key])
            ->assertOk()
            ->assertJsonPath('count', 0);

        $this->assertDatabaseHas('alert_dismissals', [
            'user_id' => $user->id,
            'alert_key' => $key,
        ]);
    }

    public function test_all_alerts_can_be_dismissed_at_once(): void
    {
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $this->makeAnimal($user, ['ear_tag' => '001', 'birth_date' => '2026-01-01']);
        $this->makeAnimal($user, ['ear_tag' => '002', 'birth_date' => '2026-01-01']);

        Carbon::setTestNow('2026-08-01');

        $this->actingAs($user)->getJson('/alertas')->assertJsonPath('count', 2);

        $this->actingAs($user)->postJson('/alertas/ocultar-todas')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_alerts_are_private_to_the_owner(): void
    {
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        Carbon::setTestNow('2026-08-01');

        $this->actingAs($other)->getJson('/alertas')->assertOk()->assertJsonPath('count', 0);
    }

    public function test_alerts_require_authentication(): void
    {
        $this->getJson('/alertas')->assertUnauthorized();
    }
}
