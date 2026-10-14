<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_available_to_guests(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Register'));
    }

    public function test_guest_can_register_and_is_signed_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Rancho del Sol',
            'email' => 'owner@example.com',
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
        ]);

        $user = User::where('email', 'owner@example.com')->firstOrFail();

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Rancho del Sol', $user->name);
        $this->assertTrue(Hash::check('StrongPassword123', $user->password));
    }

    public function test_dashboard_is_protected_after_logging_out_from_a_newly_registered_account(): void
    {
        $this->post('/register', [
            'name' => 'Rancho del Sol',
            'email' => 'owner@example.com',
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
        ])->assertRedirect('/dashboard');

        $this->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('inertia.clear_history', true);

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_registration_rejects_duplicate_email_and_mismatched_password(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->from('/register')
            ->post('/register', [
                'name' => 'Another Rancho',
                'email' => 'owner@example.com',
                'password' => 'StrongPassword123',
                'password_confirmation' => 'DifferentPassword123',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['email', 'password']);
    }
}
