<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Lote;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VaccinationManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeAnimal(User $user, Finca $finca, string $tag, string $sex = 'hembra'): Animal
    {
        return $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => $tag,
            'sex' => $sex,
            'record_type' => 'comercial',
        ]);
    }

    private function vaccinationPayload(array $overrides = []): array
    {
        return array_merge([
            'vaccination_date' => '2026-10-01',
            'vaccine_name' => 'Aftosa',
            'vaccine_class' => 'Viral',
            'vaccinator' => 'Dr. Rivas',
            'scope' => 'todo',
            'notes' => 'Refuerzo anual',
        ], $overrides);
    }

    public function test_vaccination_list_renders_with_classes_and_previous_vaccine_suggestions(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Roble']);
        $animal = $this->makeAnimal($user, $finca, 'VAC-01');

        $this->actingAs($user)->post('/vacunaciones', $this->vaccinationPayload([
            'scope' => 'individual',
            'animal_ids' => [$animal->id],
        ]))->assertRedirect('/vacunaciones');

        $vaccination = Vaccination::firstOrFail();
        $this->assertDatabaseHas('animal_vaccination', [
            'vaccination_id' => $vaccination->id,
            'animal_id' => $animal->id,
        ]);

        $this->get('/vacunaciones')->assertInertia(fn (Assert $page) => $page
            ->component('Vacunaciones/Index')
            ->has('vaccinations.data', 1)
            ->where('vaccinations.data.0.vaccine_name', 'Aftosa')
            ->where('vaccinations.data.0.vaccine_class', 'Viral')
            ->where('vaccinations.data.0.vaccinator', 'Dr. Rivas')
            ->where('vaccinations.data.0.scope', 'individual')
            ->where('vaccinations.data.0.animals_count', 1)
            ->has('vaccineClasses')
            ->has('vaccineSuggestions', 1));
    }

    public function test_vaccinating_the_whole_herd_covers_every_unsold_animal(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Cañada']);
        $first = $this->makeAnimal($user, $finca, 'HERD-01');
        $second = $this->makeAnimal($user, $finca, 'HERD-02', 'macho');
        $sold = $this->makeAnimal($user, $finca, 'HERD-03');
        $venta = $user->ventas()->create([
            'sale_date' => '2026-09-30',
            'total_price' => '900000',
            'buyer_name' => 'Ganadería El Sol',
        ]);
        $sold->ventas()->attach($venta);

        $this->actingAs($user)->post('/vacunaciones', $this->vaccinationPayload())
            ->assertRedirect('/vacunaciones');

        $vaccination = Vaccination::firstOrFail();
        $this->assertSame('todo', $vaccination->scope);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $vaccination->animals()->pluck('animals.id')->all(),
        );

        $this->getJson("/vacunaciones/{$vaccination->id}")
            ->assertOk()
            ->assertJsonPath('vaccination.animals_count', 2);
    }

    public function test_vaccinating_a_lot_requires_the_lot_and_covers_its_members(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Retiro']);
        $member = $this->makeAnimal($user, $finca, 'LOT-V-01');
        $outsider = $this->makeAnimal($user, $finca, 'LOT-V-02');
        $lote = $user->lotes()->create(['name' => 'Novillas']);
        $lote->animals()->attach($member);

        $this->actingAs($user)->post('/vacunaciones', $this->vaccinationPayload(['scope' => 'lote']))
            ->assertSessionHasErrors('lote_id');

        $this->post('/vacunaciones', $this->vaccinationPayload([
            'scope' => 'lote',
            'lote_id' => $lote->id,
        ]))->assertRedirect('/vacunaciones');

        $vaccination = Vaccination::firstOrFail();
        $this->assertSame($lote->id, $vaccination->lote_id);
        $this->assertEqualsCanonicalizing([$member->id], $vaccination->animals()->pluck('animals.id')->all());
        $this->assertDatabaseMissing('animal_vaccination', ['animal_id' => $outsider->id]);

        $this->getJson('/vacunaciones/create')
            ->assertOk()
            ->assertJsonPath('lotes.0.id', $lote->id)
            ->assertJsonPath('lotes.0.animals_count', 1)
            ->assertJsonPath('animals.0.ear_tag', 'LOT-V-01');
    }

    public function test_group_and_individual_scopes_validate_the_selected_animals(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Nogal']);
        $otherFinca = $otherUser->fincas()->create(['name' => 'Ajena']);
        $first = $this->makeAnimal($user, $finca, 'GRP-01');
        $second = $this->makeAnimal($user, $finca, 'GRP-02');
        $foreign = $this->makeAnimal($otherUser, $otherFinca, 'FOREIGN-V');

        $this->actingAs($user)
            ->post('/vacunaciones', $this->vaccinationPayload(['scope' => 'grupo']))
            ->assertSessionHasErrors('animal_ids');

        $this->post('/vacunaciones', $this->vaccinationPayload([
            'scope' => 'grupo',
            'animal_ids' => [$first->id, $foreign->id],
        ]))->assertSessionHasErrors('animal_ids.1');

        $this->post('/vacunaciones', $this->vaccinationPayload([
            'scope' => 'individual',
            'animal_ids' => [$first->id, $second->id],
        ]))->assertSessionHasErrors('animal_ids');

        $this->post('/vacunaciones', $this->vaccinationPayload([
            'scope' => 'grupo',
            'animal_ids' => [$first->id, $second->id],
        ]))->assertRedirect('/vacunaciones');

        $vaccination = Vaccination::firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $vaccination->animals()->pluck('animals.id')->all(),
        );
        $this->assertDatabaseCount('vaccinations', 1);
    }

    public function test_vaccination_date_cannot_be_in_the_future_and_records_stay_private(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'San José']);
        $animal = $this->makeAnimal($user, $finca, 'DATE-01');

        $this->actingAs($user)->post('/vacunaciones', $this->vaccinationPayload([
            'vaccination_date' => '2099-01-01',
            'scope' => 'individual',
            'animal_ids' => [$animal->id],
        ]))->assertSessionHasErrors('vaccination_date');

        $this->post('/vacunaciones', $this->vaccinationPayload([
            'scope' => 'individual',
        ]))->assertSessionHasErrors('animal_ids');

        $this->assertDatabaseCount('vaccinations', 0);

        $vaccination = $user->vaccinations()->create($this->vaccinationPayload());

        $this->actingAs($otherUser)->get("/vacunaciones/{$vaccination->id}/edit")->assertNotFound();
        $this->actingAs($otherUser)->getJson("/vacunaciones/{$vaccination->id}")->assertNotFound();
        $this->actingAs($otherUser)->delete("/vacunaciones/{$vaccination->id}")->assertNotFound();
        $this->assertDatabaseCount('vaccinations', 1);
    }

    public function test_updating_the_scope_resyncs_animals_and_deleting_cleans_the_pivot(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Esperanza']);
        $first = $this->makeAnimal($user, $finca, 'UPD-01');
        $second = $this->makeAnimal($user, $finca, 'UPD-02');
        $lote = $user->lotes()->create(['name' => 'Vaquillonas']);
        $lote->animals()->attach($second);

        $vaccination = $user->vaccinations()->create($this->vaccinationPayload([
            'scope' => 'individual',
        ]));
        $vaccination->animals()->attach($first);

        $this->actingAs($user)->put("/vacunaciones/{$vaccination->id}", $this->vaccinationPayload([
            'vaccine_name' => 'Clostridiosis 8',
            'vaccine_class' => 'Bacteriana',
            'scope' => 'lote',
            'lote_id' => $lote->id,
        ]))->assertRedirect('/vacunaciones');

        $vaccination->refresh();
        $this->assertSame('Clostridiosis 8', $vaccination->vaccine_name);
        $this->assertSame('Bacteriana', $vaccination->vaccine_class);
        $this->assertEqualsCanonicalizing([$second->id], $vaccination->animals()->pluck('animals.id')->all());
        $this->assertDatabaseMissing('animal_vaccination', ['vaccination_id' => $vaccination->id, 'animal_id' => $first->id]);

        $this->delete("/vacunaciones/{$vaccination->id}")->assertRedirect('/vacunaciones');
        $this->assertDatabaseCount('vaccinations', 0);
        $this->assertDatabaseCount('animal_vaccination', 0);
        $this->assertDatabaseHas('animals', ['id' => $second->id]);
    }

    public function test_a_sold_animal_cannot_be_added_to_a_new_vaccination(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Porvenir']);
        $sold = $this->makeAnimal($user, $finca, 'SOLD-V-01');
        $venta = $user->ventas()->create([
            'sale_date' => '2026-09-15',
            'total_price' => '750000',
            'buyer_name' => 'Carlos Ruiz',
        ]);
        $sold->ventas()->attach($venta);

        $this->actingAs($user)->post('/vacunaciones', $this->vaccinationPayload([
            'scope' => 'individual',
            'animal_ids' => [$sold->id],
        ]))->assertSessionHasErrors('animal_ids');

        $this->assertDatabaseCount('vaccinations', 0);
    }

    public function test_archived_animals_remain_visible_and_editable_in_their_vaccination_records(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Roble']);
        $animal = $this->makeAnimal($user, $finca, 'ARC-01');

        $vaccination = $user->vaccinations()->create($this->vaccinationPayload(['scope' => 'individual']));
        $vaccination->animals()->attach($animal);
        $animal->delete();

        $this->actingAs($user)->getJson("/vacunaciones/{$vaccination->id}/edit")
            ->assertOk()
            ->assertJsonPath('vaccination.animals.0.ear_tag', 'ARC-01');

        $this->getJson("/vacunaciones/{$vaccination->id}")
            ->assertOk()
            ->assertJsonPath('vaccination.animals.0.ear_tag', 'ARC-01');

        $this->put("/vacunaciones/{$vaccination->id}", $this->vaccinationPayload([
            'vaccine_name' => 'Refuerzo',
            'scope' => 'individual',
            'animal_ids' => [$animal->id],
        ]))->assertRedirect('/vacunaciones');

        $this->assertSame('Refuerzo', $vaccination->fresh()->vaccine_name);
        $this->assertDatabaseHas('animal_vaccination', [
            'vaccination_id' => $vaccination->id,
            'animal_id' => $animal->id,
        ]);
    }
}
