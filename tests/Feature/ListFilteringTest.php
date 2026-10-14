<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Lote;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListFilteringTest extends TestCase
{
    use RefreshDatabase;

    private function makeAnimal(User $user, Finca $finca, string $tag, array $overrides = []): Animal
    {
        return $user->animals()->create(array_merge([
            'finca_id' => $finca->id,
            'ear_tag' => $tag,
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ], $overrides));
    }

    public function test_animal_list_filters_by_text_finca_sex_acquisition_and_status(): void
    {
        $user = User::factory()->create();
        $roble = $user->fincas()->create(['name' => 'El Roble']);
        $cerro = $user->fincas()->create(['name' => 'El Cerro']);

        $this->makeAnimal($user, $roble, 'A-01', ['name' => 'Luna', 'sex' => 'hembra', 'acquisition_type' => 'born']);
        $this->makeAnimal($user, $cerro, 'A-02', ['name' => 'Toro Bravo', 'sex' => 'macho', 'acquisition_type' => 'purchased', 'breed' => 'Cebú']);
        $sold = $this->makeAnimal($user, $roble, 'A-03', ['sex' => 'macho']);
        $user->ventas()->create(['sale_date' => '2026-09-01', 'total_price' => 100, 'buyer_name' => 'Comprador X'])
            ->animals()->attach($sold->id);
        $archived = $this->makeAnimal($user, $roble, 'A-04', ['name' => 'Archivada']);
        $archived->delete();

        $this->actingAs($user)->get('/animales?q=Luna')->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Index')
            ->has('animals.data', 1)
            ->where('animals.data.0.ear_tag', 'A-01'));

        $this->get("/animales?q=Cebú")->assertInertia(fn (Assert $page) => $page
            ->has('animals.data', 1)
            ->where('animals.data.0.ear_tag', 'A-02'));

        $this->get("/animales?finca_id={$cerro->id}")->assertInertia(fn (Assert $page) => $page
            ->has('animals.data', 1)
            ->where('animals.data.0.ear_tag', 'A-02'));

        $this->get('/animales?sex=macho')->assertInertia(fn (Assert $page) => $page
            ->has('animals.data', 2));

        $this->get('/animales?acquisition_type=purchased')->assertInertia(fn (Assert $page) => $page
            ->has('animals.data', 1)
            ->where('animals.data.0.ear_tag', 'A-02'));

        // Por defecto no se ven archivados.
        $this->get('/animales')->assertInertia(fn (Assert $page) => $page->has('animals.data', 3));

        $this->get('/animales?estado=vendido')->assertInertia(fn (Assert $page) => $page
            ->has('animals.data', 1)
            ->where('animals.data.0.ear_tag', 'A-03'));

        $this->get('/animales?estado=todos')->assertInertia(fn (Assert $page) => $page->has('animals.data', 4));
    }

    public function test_animal_search_and_filters_can_be_combined_and_paginate_with_query_string(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'La Vega']);

        foreach (range(1, 20) as $index) {
            $this->makeAnimal($user, $finca, sprintf('LOT-%02d', $index), [
                'name' => "Vaca {$index}",
                'sex' => 'hembra',
            ]);
        }
        $this->makeAnimal($user, $finca, 'M-01', ['name' => 'Vaca Macho', 'sex' => 'macho']);

        $this->actingAs($user)->get('/animales?q=Vaca&sex=hembra')->assertInertia(fn (Assert $page) => $page
            ->component('Animales/Index')
            ->has('animals.data', 15)
            ->where('animals.total', 20)
            ->where('animals.links.1.url', fn ($url) => str_contains((string) $url, 'q=Vaca') && str_contains((string) $url, 'sex=hembra')));
    }

    public function test_vaccination_list_filters_by_text_class_scope_and_date_range(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Roble']);
        $animal = $this->makeAnimal($user, $finca, 'V-01');

        $this->actingAs($user)->post('/vacunaciones', [
            'vaccination_date' => '2026-09-15',
            'vaccine_name' => 'Aftosa',
            'vaccine_class' => 'Viral',
            'vaccinator' => 'Dr. Rivas',
            'scope' => 'individual',
            'animal_ids' => [$animal->id],
        ])->assertRedirect('/vacunaciones');

        $this->post('/vacunaciones', [
            'vaccination_date' => '2026-10-05',
            'vaccine_name' => 'Brucelosis',
            'vaccine_class' => 'Bacteriana',
            'vaccinator' => 'Dra. Gómez',
            'scope' => 'todo',
        ])->assertRedirect('/vacunaciones');

        $this->get('/vacunaciones?q=Rivas')->assertInertia(fn (Assert $page) => $page
            ->component('Vacunaciones/Index')
            ->has('vaccinations.data', 1)
            ->where('vaccinations.data.0.vaccine_name', 'Aftosa'));

        $this->get('/vacunaciones?clase=Bacteriana')->assertInertia(fn (Assert $page) => $page
            ->has('vaccinations.data', 1)
            ->where('vaccinations.data.0.vaccine_name', 'Brucelosis'));

        $this->get('/vacunaciones?alcance=individual')->assertInertia(fn (Assert $page) => $page
            ->has('vaccinations.data', 1)
            ->where('vaccinations.data.0.vaccine_name', 'Aftosa'));

        $this->get('/vacunaciones?desde=2026-10-01')->assertInertia(fn (Assert $page) => $page
            ->has('vaccinations.data', 1)
            ->where('vaccinations.data.0.vaccine_name', 'Brucelosis'));

        $this->get('/vacunaciones?desde=2026-09-01&hasta=2026-09-30')->assertInertia(fn (Assert $page) => $page
            ->has('vaccinations.data', 1)
            ->where('vaccinations.data.0.vaccine_name', 'Aftosa'));
    }

    public function test_sales_list_filters_by_buyer_animal_and_date_range(): void
    {
        $user = User::factory()->create();
        $finca = $user->fincas()->create(['name' => 'El Roble']);
        $animal = $this->makeAnimal($user, $finca, 'S-01', ['name' => 'Estrella']);

        $this->actingAs($user)->post('/ventas', [
            'animal_ids' => [$animal->id],
            'sale_date' => '2026-08-20',
            'total_price' => '1500000',
            'buyer_name' => 'Hacienda del Río',
        ])->assertRedirect('/ventas');

        $this->get('/ventas?q=del Río')->assertInertia(fn (Assert $page) => $page
            ->component('Ventas/Index')
            ->has('ventas', 1)
            ->where('ventas.0.buyer_name', 'Hacienda del Río'));

        $this->get('/ventas?q=Estrella')->assertInertia(fn (Assert $page) => $page->has('ventas', 1));
        $this->get('/ventas?q=S-01')->assertInertia(fn (Assert $page) => $page->has('ventas', 1));
        $this->get('/ventas?q=inexistente')->assertInertia(fn (Assert $page) => $page->has('ventas', 0));

        $this->get('/ventas?desde=2026-08-01&hasta=2026-08-31')->assertInertia(fn (Assert $page) => $page->has('ventas', 1));
        $this->get('/ventas?desde=2026-09-01')->assertInertia(fn (Assert $page) => $page->has('ventas', 0));
    }

    public function test_farms_lots_and_owners_lists_filter_by_text_and_status(): void
    {
        $user = User::factory()->create();
        $activa = $user->fincas()->create(['name' => 'La Esperanza', 'location' => 'Yariguíes', 'is_active' => true]);
        $user->fincas()->create(['name' => 'El Desierto', 'location' => 'Valle', 'is_active' => false]);

        $lote = $user->lotes()->create(['name' => 'Lote Norte', 'description' => 'Vacas ordas']);
        $user->lotes()->create(['name' => 'Lote Sur']);

        $this->makeAnimal($user, $activa, 'P-01');
        $user->propietarios()->create(['name' => 'María', 'phone' => '3001234567', 'is_active' => true]);
        $user->propietarios()->create(['name' => 'Pedro', 'phone' => '3009876543', 'is_active' => false]);

        $this->actingAs($user)->get('/fincas?q=Esperanza')->assertInertia(fn (Assert $page) => $page
            ->component('Fincas/Index')
            ->has('fincas', 1)
            ->where('fincas.0.name', 'La Esperanza'));

        $this->get('/fincas?estado=inactiva')->assertInertia(fn (Assert $page) => $page
            ->has('fincas', 1)
            ->where('fincas.0.name', 'El Desierto'));

        $this->get('/lotes?q=ordas')->assertInertia(fn (Assert $page) => $page
            ->component('Lotes/Index')
            ->has('lotes', 1)
            ->where('lotes.0.name', 'Lote Norte'));

        $this->get('/propietarios?q=3001234567')->assertInertia(fn (Assert $page) => $page
            ->component('Propietarios/Index')
            ->has('propietarios', 1)
            ->where('propietarios.0.name', 'María'));

        $this->get('/propietarios?estado=inactivo')->assertInertia(fn (Assert $page) => $page
            ->has('propietarios', 1)
            ->where('propietarios.0.name', 'Pedro'));
    }

    public function test_list_filters_stay_private_to_the_authenticated_owner(): void
    {
        $owner = User::factory()->create();
        $finca = $owner->fincas()->create(['name' => 'Ajena']);
        $owner->animals()->create([
            'finca_id' => $finca->id,
            'ear_tag' => 'FOREIGN-01',
            'sex' => 'hembra',
            'record_type' => 'comercial',
        ]);
        $owner->lotes()->create(['name' => 'Lote ajeno']);
        $owner->propietarios()->create(['name' => 'Ajeno']);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get('/animales?q=FOREIGN')->assertInertia(fn (Assert $page) => $page->has('animals.data', 0));
        $this->get('/lotes?q=Lote ajeno')->assertInertia(fn (Assert $page) => $page->has('lotes', 0));
        $this->get('/propietarios?q=Ajeno')->assertInertia(fn (Assert $page) => $page->has('propietarios', 0));
        $this->get('/fincas?q=Ajena')->assertInertia(fn (Assert $page) => $page->has('fincas', 0));
    }
}
