<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseAgeOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'finca_id' => $user->fincas()->create(['name' => 'La Loma'])->id,
            'sex' => 'macho',
            'acquisition_type' => 'purchased',
            'purchase_source' => 'feria',
            'record_type' => 'comercial',
        ], $overrides);
    }

    public function test_a_purchase_age_from_the_fixed_list_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/animales', $this->payload($user, [
            'ear_tag' => 'A-1',
            'age_at_purchase_months' => 24,
        ]))->assertRedirect('/animales');

        $this->assertSame(24, Animal::where('ear_tag', 'A-1')->firstOrFail()->age_at_purchase_months);
    }

    public function test_a_purchase_age_outside_the_list_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/animales', $this->payload($user, [
            'ear_tag' => 'A-1',
            'age_at_purchase_months' => 18,
        ]))->assertSessionHasErrors('age_at_purchase_months');

        $this->assertDatabaseMissing('animals', ['ear_tag' => 'A-1']);
    }

    public function test_the_fixed_list_is_shared_with_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/animales/create')
            ->assertInertia(fn ($page) => $page
                ->where('purchaseAgeOptions.0.value', 6)
                ->where('purchaseAgeOptions.0.label', '6 meses')
                ->where('purchaseAgeOptions.7.value', 84)
                ->where('purchaseAgeOptions.7.label', '7 años'));
    }
}
