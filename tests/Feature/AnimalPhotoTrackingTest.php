<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnimalPhotoTrackingTest extends TestCase
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

    public function test_an_animal_registered_young_is_asked_for_a_photo_at_six_months(): void
    {
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/animales', [
            'finca_id' => $user->fincas()->create(['name' => 'La Loma'])->id,
            'sex' => 'hembra',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
            'birth_date' => '2026-01-01',
        ])->assertRedirect('/animales');

        $animal = Animal::firstOrFail();

        $this->assertSame('2026-07-01', $animal->next_photo_due_on->toDateString());
        $this->assertFalse($animal->photo_needs_update);
    }

    public function test_an_animal_registered_as_an_adult_is_not_tracked(): void
    {
        Carbon::setTestNow('2026-10-01');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/animales', [
            'finca_id' => $user->fincas()->create(['name' => 'La Loma'])->id,
            'sex' => 'hembra',
            'acquisition_type' => 'born',
            'record_type' => 'comercial',
            // 33 meses al registrarse: ya es reconocible, no se le piden fotos.
            'birth_date' => '2024-01-01',
        ])->assertRedirect('/animales');

        $this->assertNull(Animal::firstOrFail()->next_photo_due_on);
    }

    public function test_a_photo_at_the_milestone_advances_the_next_reminder(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $animal = $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        $this->assertSame('2026-07-01', $animal->fresh()->next_photo_due_on->toDateString());

        Carbon::setTestNow('2026-07-10');

        $this->actingAs($user)->post("/animales/{$animal->id}/fotos", [
            'photo' => UploadedFile::fake()->create('cabra.jpg', 1, 'image/jpeg'),
            'taken_at' => '2026-07-10',
        ])->assertRedirect();

        // El hito de 6 meses queda cubierto y el siguiente aviso es a los 24 meses.
        $this->assertSame('2028-01-01', $animal->fresh()->next_photo_due_on->toDateString());
        $this->assertSame(1, $animal->photos()->count());
    }

    public function test_an_animal_is_listed_as_needing_a_photo_once_the_milestone_passes(): void
    {
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        $this->get('/animales?foto=pendiente')->assertInertia(fn ($page) => $page->has('animals.data', 0));

        Carbon::setTestNow('2026-08-01');

        $this->get('/animales?foto=pendiente')->assertInertia(fn ($page) => $page
            ->has('animals.data', 1)
            ->where('animals.data.0.photo_needs_update', true)
            ->where('animals.data.0.photo_due_stage', '6 meses'));
    }

    public function test_the_dashboard_counts_animals_needing_a_photo(): void
    {
        $user = User::factory()->create();

        Carbon::setTestNow('2026-01-01');
        $this->makeAnimal($user, ['ear_tag' => '001', 'birth_date' => '2026-01-01']); // aviso a los 6 meses (julio)

        Carbon::setTestNow('2026-06-01');
        $this->makeAnimal($user, ['ear_tag' => '002', 'birth_date' => '2026-06-01']); // aviso a los 6 meses (diciembre)

        Carbon::setTestNow('2026-09-01');

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('animalsNeedingPhotoCount', 1));
    }

    public function test_a_photo_can_be_removed_from_the_history(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-01-01');
        $user = User::factory()->create();
        $animal = $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        Carbon::setTestNow('2026-07-10');

        $this->actingAs($user)->post("/animales/{$animal->id}/fotos", [
            'photo' => UploadedFile::fake()->create('cabra.jpg', 1, 'image/jpeg'),
            'taken_at' => '2026-07-10',
        ]);

        $photo = $animal->photos()->firstOrFail();
        Storage::disk('public')->assertExists($photo->path);

        $this->delete("/animales/{$animal->id}/fotos/{$photo->id}")->assertRedirect();

        $this->assertSame(0, $animal->photos()->count());
        $this->assertNull($animal->fresh()->photo_path);
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_photos_are_private_to_the_owner(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $animal = $this->makeAnimal($user, ['birth_date' => '2026-01-01']);

        $this->actingAs($other)->post("/animales/{$animal->id}/fotos", [
            'photo' => UploadedFile::fake()->create('cabra.jpg', 1, 'image/jpeg'),
        ])->assertNotFound();
    }
}
