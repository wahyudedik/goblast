<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoogleButtonVisibilityTest extends TestCase
{
    public function test_google_button_visible_on_login_page_when_configured(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Login dengan Google');
        $response->assertSee(route('auth.google.redirect'));
    }

    public function test_google_button_hidden_on_login_page_when_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertDontSee('Login dengan Google');
    }

    public function test_google_button_visible_on_register_page_when_configured(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Daftar dengan Google');
        $response->assertSee(route('auth.google.redirect'));
    }

    public function test_google_button_hidden_on_register_page_when_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertDontSee('Daftar dengan Google');
    }

    public function test_google_button_hidden_when_client_id_is_empty_string(): void
    {
        config(['services.google.client_id' => '']);

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertDontSee('Login dengan Google');
    }
}
