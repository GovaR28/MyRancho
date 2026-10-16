<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/perfil')->assertRedirect('/login');
    }

    public function test_profile_page_is_available_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/perfil')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Perfil/Edit'));
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Rancho Viejo',
            'email' => 'owner@example.com',
        ]);

        $this->actingAs($user)
            ->from('/perfil')
            ->put('/perfil', [
                'name' => 'Rancho del Sol',
                'email' => 'nuevo@example.com',
            ])
            ->assertRedirect('/perfil')
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Rancho del Sol', $user->name);
        $this->assertSame('nuevo@example.com', $user->email);
    }

    public function test_user_can_change_password_with_the_correct_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'OldPassword123',
        ]);

        $this->actingAs($user)
            ->put('/perfil', [
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('NewPassword123', $user->refresh()->password));
    }

    public function test_changing_password_requires_the_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'OldPassword123',
        ]);

        $this->actingAs($user)
            ->from('/perfil')
            ->put('/perfil', [
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'WrongPassword123',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect('/perfil')
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('OldPassword123', $user->refresh()->password));
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->actingAs($user)
            ->from('/perfil')
            ->put('/perfil', [
                'name' => $user->name,
                'email' => 'taken@example.com',
            ])
            ->assertRedirect('/perfil')
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_keep_their_own_email(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->actingAs($user)
            ->from('/perfil')
            ->put('/perfil', [
                'name' => 'Rancho del Sol',
                'email' => 'owner@example.com',
            ])
            ->assertRedirect('/perfil')
            ->assertSessionHasNoErrors();
    }
}
