<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);
    }

    public function test_login_stores_the_authenticated_session_in_the_database(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => 'owner@example.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id,
        ]);
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('inertia.clear_history', true);

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_response_is_not_cached_by_the_browser(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}
