<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarTagAndExitTest extends TestCase
{
    use RefreshDatabase;

    private function animalData(int $fincaId, array $overrides = []): array
    {
        return array_merge([
            'finca_id' => $fincaId,
            'sex' => 'hembra',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
        ], $overrides);
    }

    public function test_ear_tag_is_assigned_automatically_and_sequentially(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Loma']);

        $this->actingAs($user)->post('/animales', $this->animalData($finca->id))->assertRedirect('/animales');
        $this->post('/animales', $this->animalData($finca->id))->assertRedirect('/animales');

        $this->assertSame(['001', '002'], Animal::orderBy('id')->pluck('ear_tag')->all());
    }

    public function test_ear_tag_numbers_are_not_reused_after_sale_or_baja(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Loma']);

        $this->actingAs($user)->post('/animales', $this->animalData($finca->id));
        $this->post('/animales', $this->animalData($finca->id));

        $first = Animal::where('ear_tag', '001')->firstOrFail();
        $second = Animal::where('ear_tag', '002')->firstOrFail();

        // El 001 se vende y el 002 se da de baja.
        $user->ventas()->create(['sale_date' => '2026-10-01', 'total_price' => 1000, 'buyer_name' => 'Comprador'])
            ->animals()->attach($first->id);
        $this->post("/animales/{$second->id}/baja", [
            'exit_date' => '2026-10-02',
            'exit_reason' => 'Muerte',
        ])->assertRedirect('/animales');

        // El siguiente animal continúa la serie; nunca reutiliza 001 ni 002.
        $this->post('/animales', $this->animalData($finca->id))->assertRedirect('/animales');

        $this->assertSame('003', Animal::orderByDesc('id')->value('ear_tag'));
    }

    public function test_giving_baja_records_reason_and_hides_the_animal_from_the_active_list(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Loma']);
        $animal = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => '001',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);

        $this->actingAs($user)->post("/animales/{$animal->id}/baja", [
            'exit_date' => '2026-10-10',
            'exit_reason' => 'Robo',
            'exit_notes' => 'Se perdió en el potrero norte.',
        ])->assertRedirect('/animales');

        $this->assertSoftDeleted('animals', [
            'id' => $animal->id,
            'exit_reason' => 'Robo',
        ]);
        $this->assertSame('2026-10-10', $animal->fresh()->exit_date->toDateString());

        $this->get('/animales?estado=activo')->assertInertia(fn ($page) => $page->has('animals.data', 0));

        $this->get('/animales?estado=baja')->assertInertia(fn ($page) => $page
            ->has('animals.data', 1)
            ->where('animals.data.0.ear_tag', '001'));
    }

    public function test_giving_baja_requires_a_date_and_reason(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Loma']);
        $animal = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => '001',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);

        $this->actingAs($user)->from('/animales')->post("/animales/{$animal->id}/baja", [])
            ->assertRedirect('/animales')
            ->assertSessionHasErrors(['exit_date', 'exit_reason']);

        $this->assertNotSoftDeleted('animals', ['id' => $animal->id]);
    }

    public function test_a_sold_animal_cannot_be_given_baja(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Loma']);
        $animal = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => '001',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);

        $user->ventas()->create(['sale_date' => '2026-10-01', 'total_price' => 1000, 'buyer_name' => 'Comprador'])
            ->animals()->attach($animal->id);

        $this->actingAs($user)->from('/animales')->post("/animales/{$animal->id}/baja", [
            'exit_date' => '2026-10-02',
            'exit_reason' => 'Muerte',
        ])->assertSessionHasErrors('exit_reason');

        $this->assertNotSoftDeleted('animals', ['id' => $animal->id]);
    }
}
