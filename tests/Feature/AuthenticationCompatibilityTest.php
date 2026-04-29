<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationCompatibilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_normal_user_can_login_with_email_and_password(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'normal@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'normal@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_only_user_gets_error_when_login_via_password_form(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'googleonly@example.com',
            'password' => null,
            'google_id' => 'google-123456',
        ]);

        $response = $this->post('/login', [
            'email' => 'googleonly@example.com',
            'password' => 'any-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $errors = session('errors');
        $this->assertStringContainsString(
            'Akun ini terdaftar menggunakan Google',
            $errors->first('email')
        );
    }

    public function test_user_with_both_password_and_google_id_can_login_with_password(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'hybrid@example.com',
            'password' => Hash::make('password123'),
            'google_id' => 'google-789',
        ]);

        $response = $this->post('/login', [
            'email' => 'hybrid@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_generic_error(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'wrongpass@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'wrongpass@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Should show generic auth.failed message, not Google-specific message
        $errors = session('errors');
        $this->assertStringNotContainsString(
            'Google',
            $errors->first('email')
        );
    }

    public function test_nonexistent_user_shows_generic_error(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
