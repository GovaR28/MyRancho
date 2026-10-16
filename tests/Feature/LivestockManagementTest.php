<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Lote;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LivestockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_finca_or_owner_from_the_animal_form_returns_to_it_with_updated_options(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->from('/animales/create')->post('/fincas', [
            'name' => 'El Roble',
            'return_to_animal' => 'create',
        ])->assertRedirect('/animales/create');

        $finca = Finca::where('name', 'El Roble')->firstOrFail();
        $this->assertSame($finca->id, session('created_finca_id'));
        $this->get('/animales/create')->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Form')
            ->has('fincas', 1)
            ->where('fincas.0.id', $finca->id));
        $this->getJson('/animales/create')
            ->assertOk()
            ->assertJsonPath('animal', null)
            ->assertJsonPath('fincas.0.id', $finca->id);

        $animal = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'MR-EDIT',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);

        $this->from("/animales/{$animal->id}/edit")->post('/propietarios', [
            'name' => 'Ana Pérez',
            'relationship' => 'Hermana',
            'return_to_animal' => (string) $animal->id,
        ])->assertRedirect("/animales/{$animal->id}/edit");

        $propietario = Propietario::where('name', 'Ana Pérez')->firstOrFail();
        $this->assertSame($propietario->id, session('created_propietario_id'));
        $this->get("/animales/{$animal->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Form')
            ->where('animal.id', $animal->id)
            ->has('propietarios', 1)
            ->where('propietarios.0.id', $propietario->id));

        $this->getJson("/animales/{$animal->id}/edit")
            ->assertOk()
            ->assertJsonPath('animal.id', $animal->id)
            ->assertJsonPath('fincas.0.id', $finca->id)
            ->assertJsonPath('propietarios.0.id', $propietario->id);
    }

    public function test_creating_a_finca_from_the_animal_list_returns_to_the_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/animales')
            ->post('/fincas', [
                'name' => 'La Pradera',
                'return_to_animal' => 'index',
            ]);

        $finca = Finca::where('name', 'La Pradera')->firstOrFail();
        $response->assertRedirect('/animales')
            ->assertSessionHas('created_finca_id', $finca->id);
    }

    public function test_owner_can_manage_fincas_and_archive_without_losing_animal_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/fincas', [
            'name' => 'El Roble',
            'location' => 'Antioquia',
            'area_hectares' => 12.5,
        ])->assertRedirect('/fincas');

        $finca = Finca::where('name', 'El Roble')->firstOrFail();
        $animal = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'MR-001',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);

        $this->patch("/fincas/{$finca->id}/estado")->assertRedirect();
        $this->assertDatabaseHas('fincas', ['id' => $finca->id, 'is_active' => false]);

        $this->delete("/fincas/{$finca->id}")->assertRedirect('/fincas');

        $this->assertSoftDeleted('fincas', ['id' => $finca->id]);
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'finca_id' => $finca->id]);
        $this->get("/animales/{$animal->id}/edit")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Form')
            ->has('fincas', 1)
            ->where('fincas.0.id', $finca->id));
        $this->put("/animales/{$animal->id}", [
            'finca_id' => $finca->id,
            'ear_tag' => $animal->ear_tag,
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ])->assertRedirect('/animales');
        $this->get('/animales')->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Index')
            ->where('animals.data.0.finca.name', 'El Roble'));
    }

    public function test_owner_can_create_a_lot_and_add_and_remove_available_animals(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Cañada']);
        $animal1 = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'LOT-01',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);
        $animal2 = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'LOT-02',
            'sex' => 'macho',
            'record_type' => 'comercial',
        ]);

        $this->actingAs($user)->post('/lotes', [
            'name' => 'Novillas',
            'animal_ids' => [$animal1->id],
        ])->assertRedirect('/lotes');

        $lote = Lote::where('name', 'Novillas')->firstOrFail();
        $this->assertDatabaseHas('lote_animal', ['lote_id' => $lote->id, 'animal_id' => $animal1->id]);
        $this->get('/lotes')->assertInertia(fn (Assert $page) => $page
            ->component('Lotes/Index')
            ->has('lotes', 1)
            ->has('animals', 1));
        $this->get("/lotes/{$lote->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Lotes/Show')
            ->where('lote.name', 'Novillas')
            ->has('lote.animals', 1)
            ->has('animals', 1));

        $this->post("/lotes/{$lote->id}/animales", [
            'animal_ids' => [$animal2->id],
        ])->assertRedirect();
        $this->assertCount(2, $lote->animals()->get());

        $this->delete("/lotes/{$lote->id}/animales/{$animal1->id}")->assertRedirect();
        $this->assertDatabaseMissing('lote_animal', ['lote_id' => $lote->id, 'animal_id' => $animal1->id]);
    }

    public function test_lot_sale_records_total_price_buyer_and_date_for_all_unsold_members(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Retiro']);
        $animals = collect(['SALE-01', 'SALE-02'])->map(fn ($tag) => $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => $tag,
            'sex' => 'macho',
            'record_type' => 'comercial',
        ]));
        $lote = $user->lotes()->create(['name' => 'Lote de venta']);
        $lote->animals()->attach($animals->pluck('id')->all());

        $this->actingAs($user)->post('/ventas', [
            'lote_id' => $lote->id,
            'sale_date' => '2026-10-09',
            'total_price' => '1250000.50',
            'buyer_name' => 'Ganadería El Sol',
        ])->assertRedirect('/ventas');

        $venta = Venta::firstOrFail();
        $this->assertSame('1250000.50', $venta->total_price);
        $this->assertSame('Ganadería El Sol', $venta->buyer_name);
        $this->assertSame('2026-10-09', $venta->sale_date->toDateString());
        $this->assertCount(2, $venta->animals);
        $this->get('/ventas')->assertInertia(fn (Assert $page) => $page
            ->component('Ventas/Index')
            ->has('ventas', 1)
            ->where('ventas.0.buyer_name', 'Ganadería El Sol')
            ->where('ventas.0.lote.name', 'Lote de venta')
            ->has('ventas.0.animals', 2));

        $this->post('/ventas', [
            'lote_id' => $lote->id,
            'sale_date' => '2026-10-10',
            'total_price' => '100',
            'buyer_name' => 'Otra persona',
        ])->assertSessionHasErrors('lote_id');
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_individual_sale_is_recorded_and_animal_cannot_be_sold_twice(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Porvenir']);
        $animal = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'SOLO-01',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);

        $this->actingAs($user)->post('/ventas', [
            'animal_ids' => [$animal->id],
            'sale_date' => '2026-10-10',
            'total_price' => '750000',
            'buyer_name' => 'Carlos Ruiz',
        ])->assertRedirect('/ventas');

        $this->post('/ventas', [
            'animal_ids' => [$animal->id],
            'sale_date' => '2026-10-10',
            'total_price' => '800000',
            'buyer_name' => 'Otra persona',
        ])->assertSessionHasErrors('animal_ids');
        $this->assertDatabaseCount('ventas', 1);
        $this->get('/animales')->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Index')
            ->where('animals.data.0.ventas.0.buyer_name', 'Carlos Ruiz'));
    }

    public function test_lots_and_sales_are_scoped_to_the_authenticated_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $finca = $otherUser->fincas()->create(['name' => 'Ajena']);
        $animal = $otherUser->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'FOREIGN-01',
            'sex' => 'macho',
            'record_type' => 'comercial',
        ]);
        $lote = $otherUser->lotes()->create(['name' => 'Lote ajeno']);

        $this->actingAs($user)->get("/lotes/{$lote->id}")->assertNotFound();
        $this->post('/lotes', [
            'name' => 'Lote inválido',
            'animal_ids' => [$animal->id],
        ])->assertSessionHasErrors('animal_ids.0');
        $this->post('/ventas', [
            'animal_ids' => [$animal->id],
            'sale_date' => '2026-10-10',
            'total_price' => '100',
            'buyer_name' => 'Comprador',
        ])->assertSessionHasErrors('animal_ids.0');
    }

    public function test_animal_requires_a_unique_tag_owned_finca_and_correctly_typed_parents(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Esperanza']);
        $otherFinca = $otherUser->fincas()->create(['name' => 'Otra finca']);

        $mother = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'V-01',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);
        $father = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'T-01',
            'sex' => 'macho',
            'record_type' => 'comercial',
        ]);

        $data = [
            'finca_id' => $finca->id,
            'ear_tag' => 'C-01',
            'sex' => 'hembra',
            'acquisition_type' => 'born',
            'birth_date' => '2024-02-10',
            'record_type' => 'puro',
            'mother_id' => $mother->id,
            'father_id' => $father->id,
        ];

        $this->actingAs($user)->post('/animales', $data)->assertRedirect('/animales');
        $calf = Animal::where('ear_tag', 'C-01')->firstOrFail();
        $this->assertSame($mother->id, $calf->mother_id);
        $this->assertSame($father->id, $calf->father_id);

        $this->from('/animales/create')->post('/animales', [...$data, 'finca_id' => $otherFinca->id])
            ->assertRedirect('/animales/create')
            ->assertSessionHasErrors('finca_id');
        $this->from('/animales/create')->post('/animales', [...$data, 'ear_tag' => 'OTHER', 'mother_id' => $father->id])
            ->assertRedirect('/animales/create')
            ->assertSessionHasErrors('mother_id');
        $this->from('/animales/create')->post('/animales', [...$data, 'ear_tag' => 'OTHER', 'birth_date' => '2999-01-01'])
            ->assertRedirect('/animales/create')
            ->assertSessionHasErrors('birth_date');
        $this->assertDatabaseCount('animals', 3);
    }

    public function test_animal_parent_relationship_cannot_create_a_genealogy_cycle_and_baja_keeps_parents(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Nogal']);
        $grandmother = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'G-01',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);
        $mother = $user->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'M-01',
            'sex' => 'hembra',
            'mother_id' => $grandmother->id,
            'record_type' => 'comercial',
        ]);

        $this->actingAs($user)->put("/animales/{$grandmother->id}", [
            'finca_id' => $finca->id,
            'ear_tag' => 'G-01',
            'sex' => 'hembra',
            'record_type' => 'comercial',
            'mother_id' => $mother->id,
        ])->assertSessionHasErrors('mother_id');

        $this->put("/animales/{$grandmother->id}", [
            'finca_id' => $finca->id,
            'ear_tag' => 'G-01',
            'sex' => 'hembra',
            'record_type' => 'comercial',
            'mother_id' => $grandmother->id,
        ])->assertSessionHasErrors('mother_id');

        $this->post("/animales/{$grandmother->id}/baja", [
            'exit_date' => '2026-10-15',
            'exit_reason' => 'Muerte',
        ])->assertRedirect('/animales');

        $this->assertSoftDeleted('animals', [
            'id' => $grandmother->id,
            'exit_reason' => 'Muerte',
        ]);
        $this->assertSame($grandmother->id, $mother->fresh()->mother_id);
        $this->assertSame('G-01', $mother->fresh()->mother->ear_tag);
    }

    public function test_animal_photo_is_stored_and_replaced_on_update(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Porvenir']);
        $data = [
            'finca_id' => $finca->id,
            'ear_tag' => 'IMG-01',
            'sex' => 'macho',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
        ];

        $this->actingAs($user)->post('/animales', [
            ...$data,
            'photo' => UploadedFile::fake()->create('animal.jpg', 1, 'image/jpeg'),
        ])->assertRedirect('/animales');

        $animal = Animal::where('ear_tag', 'IMG-01')->firstOrFail();
        $oldPath = $animal->photo_path;
        $this->assertTrue(Storage::disk('public')->exists($oldPath));
        URL::forceRootUrl('http://localhost/MyRancho');
        $this->assertSame('http://localhost/MyRancho/storage/'.$oldPath, $animal->fresh()->photo_url);
        URL::forceRootUrl(null);
        $this->assertNotEmpty($animal->photo_url);
        $this->get('/animales')->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Index')
            ->where('animals.data.0.photo_url', asset('storage/'.$oldPath)));

        $this->put("/animales/{$animal->id}", [
            ...$data,
            'photo' => UploadedFile::fake()->create('replacement.jpg', 1, 'image/jpeg'),
        ])->assertRedirect('/animales');

        $animal->refresh();
        $this->assertNotSame($oldPath, $animal->photo_path);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertTrue(Storage::disk('public')->exists($animal->photo_path));
    }

    public function test_animals_in_my_finca_can_be_assigned_to_a_family_member(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'Finca Familiar']);

        $this->actingAs($user)->post('/propietarios', [
            'name' => 'María',
            'relationship' => 'Hermana',
            'phone' => '555-0123',
        ])->assertRedirect('/propietarios');

        $sister = Propietario::where('name', 'María')->firstOrFail();
        $this->post('/animales', [
            'finca_id' => $finca->id,
            'propietario_id' => $sister->id,
            'ear_tag' => 'HER-01',
            'name' => 'Lucera',
            'sex' => 'hembra',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
        ])->assertRedirect('/animales');

        $animal = Animal::where('ear_tag', 'HER-01')->firstOrFail();
        $this->assertSame($finca->id, $animal->finca_id);
        $this->assertSame($sister->id, $animal->propietario_id);

        $this->get('/animales')->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Index')
            ->where('animals.data.0.finca.name', 'Finca Familiar')
            ->where('animals.data.0.propietario.name', 'María'));
    }

    public function test_owner_assignment_is_scoped_to_my_account_and_inactive_owners_cannot_be_assigned_to_new_animals(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'Mi finca']);
        $otherOwner = $otherUser->propietarios()->create(['name' => 'Propietario ajeno']);
        $owner = $user->propietarios()->create(['name' => 'Propietario inactivo', 'is_active' => false]);

        $data = [
            'finca_id' => $finca->id,
            'ear_tag' => 'SCOPED-01',
            'sex' => 'macho',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
        ];

        $this->actingAs($user)
            ->from('/animales/create')
            ->post('/animales', [...$data, 'propietario_id' => $otherOwner->id])
            ->assertRedirect('/animales/create')
            ->assertSessionHasErrors('propietario_id');

        $this->from('/animales/create')
            ->post('/animales', [...$data, 'propietario_id' => $owner->id])
            ->assertRedirect('/animales/create')
            ->assertSessionHasErrors('propietario_id');

        $this->assertDatabaseCount('animals', 0);
        $this->get("/propietarios/{$otherOwner->id}/edit")->assertNotFound();
    }

    public function test_records_are_scoped_to_the_authenticated_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $finca = $owner->fincas()->create(['name' => 'Privada']);
        $animal = $owner->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'PR-01',
            'sex' => 'macho',
            'record_type' => 'comercial',
        ]);

        $this->actingAs($other)
            ->get("/animales/{$animal->id}/edit")
            ->assertNotFound();

        $this->actingAs($other)
            ->delete("/fincas/{$finca->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('fincas', ['id' => $finca->id, 'deleted_at' => null]);
    }

    public function test_purchased_animal_requires_a_source_and_stores_price_and_weight(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Vega']);
        $base = [
            'finca_id' => $finca->id,
            'ear_tag' => 'CMP-01',
            'sex' => 'hembra',
            'acquisition_type' => 'purchased',
            'record_type' => 'comercial',
            'breed' => 'Holstein',
            'birth_date' => '2024-03-15',
            'purchase_price' => '1850000',
            'purchase_weight_kg' => '312.50',
            'purchase_date' => '2026-09-20',
            'seller_name' => 'Hacienda El Cielo',
        ];

        $this->actingAs($user)->from('/animales')->post('/animales', $base)
            ->assertRedirect('/animales')
            ->assertSessionHasErrors('purchase_source');

        $this->post('/animales', [...$base, 'purchase_source' => 'criadero'])
            ->assertSessionHasErrors('purchase_source');

        $this->post('/animales', [...$base, 'purchase_source' => 'feria'])
            ->assertRedirect('/animales');

        $animal = Animal::where('ear_tag', 'CMP-01')->firstOrFail();
        $this->assertSame('feria', $animal->purchase_source);
        $this->assertSame('Holstein', $animal->breed);
        $this->assertNull($animal->birth_date);
        $this->assertSame('2026-09-20', $animal->purchase_date->toDateString());
        $this->assertSame('1850000.00', $animal->purchase_price);
        $this->assertSame('312.50', $animal->purchase_weight_kg);
    }

    public function test_purchased_cow_with_offspring_creates_both_animals_linked_as_mother(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'San Miguel']);

        $this->actingAs($user)->post('/animales', [
            'finca_id' => $finca->id,
            'ear_tag' => 'V-100',
            'sex' => 'hembra',
            'acquisition_type' => 'purchased',
            'purchase_source' => 'hacienda',
            'breed' => 'Jersey',
            'record_type' => 'comercial',
            'purchase_price' => '2400000',
            'purchase_date' => '2026-10-01',
            'seller_name' => 'Feria de Ganado',
            'came_with_offspring' => true,
            'offspring_ear_tag' => 'C-100',
            'offspring_sex' => 'macho',
            'offspring_name' => 'Chivo',
            'offspring_birth_date' => '2026-08-15',
            'offspring_weight_kg' => '42.30',
        ])->assertRedirect('/animales');

        $cow = Animal::where('ear_tag', 'V-100')->firstOrFail();
        $calf = Animal::where('mother_id', $cow->id)->firstOrFail();

        $this->assertSame($cow->id, $calf->mother_id);
        $this->assertSame($finca->id, $calf->finca_id);
        $this->assertSame('hacienda', $calf->purchase_source);
        $this->assertSame('2026-10-01', $calf->purchase_date->toDateString());
        $this->assertSame('Feria de Ganado', $calf->seller_name);
        $this->assertSame('Jersey', $cow->breed);
        $this->assertTrue($calf->came_with_mother);
        $this->assertSame('42.30', $calf->birth_weight_kg);
        $this->assertSame('Chivo', $calf->name);

        $this->from('/animales')->post('/animales', [
            'finca_id' => $finca->id,
            'ear_tag' => 'V-101',
            'sex' => 'hembra',
            'acquisition_type' => 'purchased',
            'purchase_source' => 'particular',
            'record_type' => 'comercial',
            'came_with_offspring' => '1',
        ])->assertSessionHasErrors(['offspring_sex']);

        $this->from('/animales')->post('/animales', [
            'finca_id' => $finca->id,
            'ear_tag' => 'T-102',
            'sex' => 'macho',
            'acquisition_type' => 'purchased',
            'purchase_source' => 'hacienda',
            'record_type' => 'comercial',
            'came_with_offspring' => '1',
            'offspring_ear_tag' => 'C-102',
            'offspring_sex' => 'macho',
        ])->assertSessionHasErrors('came_with_offspring');

        $this->assertDatabaseCount('animals', 2);
    }

    public function test_animal_creation_requires_an_acquisition_type(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Paraíso']);

        $this->actingAs($user)->from('/animales/create')->post('/animales', [
            'finca_id' => $finca->id,
            'ear_tag' => 'SIN-ORIGEN',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ])->assertRedirect('/animales/create')->assertSessionHasErrors('acquisition_type');

        $this->assertDatabaseCount('animals', 0);
    }

    public function test_born_animal_accepts_unknown_parents_and_other_reproduction_method(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Trapiche']);

        $this->actingAs($user)->from('/animales')->post('/animales', [
            'finca_id' => $finca->id,
            'ear_tag' => 'N-01',
            'sex' => 'hembra',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
            'birth_date' => '2026-05-10',
            'birth_weight_kg' => '34.20',
            'reproduction_method' => 'other',
        ])->assertRedirect('/animales')->assertSessionHasErrors('reproduction_method_other');

        $this->post('/animales', [
            'finca_id' => $finca->id,
            'ear_tag' => 'N-01',
            'sex' => 'hembra',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
            'birth_date' => '2026-05-10',
            'birth_weight_kg' => '34.20',
            'reproduction_method' => 'other',
            'reproduction_method_other' => 'Compra de preñez',
            'mother_id' => '',
            'mother_unknown' => true,
            'father_unknown' => true,
        ])->assertRedirect('/animales');

        $animal = Animal::where('ear_tag', 'N-01')->firstOrFail();
        $this->assertNull($animal->mother_id);
        $this->assertNull($animal->father_id);
        $this->assertTrue($animal->mother_unknown);
        $this->assertTrue($animal->father_unknown);
        $this->assertSame('other', $animal->reproduction_method);
        $this->assertSame('Compra de preñez', $animal->reproduction_method_other);
        $this->assertSame('34.20', $animal->birth_weight_kg);
    }
}
