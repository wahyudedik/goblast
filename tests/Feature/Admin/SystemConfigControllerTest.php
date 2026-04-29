<?php

namespace Tests\Feature\Admin;

use App\Models\SystemConfig;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SystemConfigControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $superadmin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->superadmin()->create();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $this->regularUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);
    }

    // --- Authorization Tests ---

    public function test_non_superadmin_cannot_access_config_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.configs.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_config_index(): void
    {
        $response = $this->get(route('admin.configs.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_config_edit(): void
    {
        $config = SystemConfig::factory()->create();

        $response = $this->actingAs($this->regularUser)->get(route('admin.configs.edit', $config));

        $response->assertForbidden();
    }

    public function test_non_superadmin_cannot_update_config(): void
    {
        $config = SystemConfig::factory()->create(['value' => '100']);

        $response = $this->actingAs($this->regularUser)->put(route('admin.configs.update', $config), [
            'value' => '200',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('system_configs', ['id' => $config->id, 'value' => '100']);
    }

    // --- Index Tests ---

    public function test_superadmin_can_view_config_index(): void
    {
        SystemConfig::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.configs.index'));

        $response->assertOk();
        $response->assertViewIs('admin.configs.index');
        $response->assertViewHas('configs');
    }

    public function test_config_index_displays_all_columns(): void
    {
        $config = SystemConfig::factory()->create([
            'key' => 'test_config_key',
            'value' => '42',
            'type' => 'integer',
            'description' => 'A test configuration',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.configs.index'));

        $response->assertOk();
        $response->assertSee('test_config_key');
        $response->assertSee('42');
        $response->assertSee('integer');
        $response->assertSee('A test configuration');
    }

    public function test_config_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.configs.index'));

        $response->assertOk();
        $response->assertSee('Tidak ada konfigurasi');
    }

    public function test_config_index_displays_boolean_badges(): void
    {
        SystemConfig::factory()->boolean()->create(['value' => 'true']);
        SystemConfig::factory()->boolean()->create(['value' => 'false']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.configs.index'));

        $response->assertOk();
        $response->assertSee('True');
        $response->assertSee('False');
    }

    // --- Edit Tests ---

    public function test_superadmin_can_view_config_edit(): void
    {
        $config = SystemConfig::factory()->create(['key' => 'my_config']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.configs.edit', $config));

        $response->assertOk();
        $response->assertViewIs('admin.configs.edit');
        $response->assertViewHas('config');
        $response->assertSee('my_config');
    }

    public function test_edit_shows_integer_input_for_integer_type(): void
    {
        $config = SystemConfig::factory()->create([
            'key' => 'default_rate_limit_per_hour',
            'value' => '200',
            'type' => 'integer',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.configs.edit', $config));

        $response->assertOk();
        $response->assertSee('type="number"', false);
        $response->assertSee('1 - 1000');
    }

    public function test_edit_shows_select_for_boolean_type(): void
    {
        $config = SystemConfig::factory()->boolean()->create(['value' => 'true']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.configs.edit', $config));

        $response->assertOk();
        $response->assertSee('<select', false);
    }

    // --- Update Tests ---

    public function test_superadmin_can_update_integer_config(): void
    {
        $config = SystemConfig::factory()->create([
            'key' => 'default_rate_limit_per_hour',
            'value' => '200',
            'type' => 'integer',
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => '500',
        ]);

        $response->assertRedirect(route('admin.configs.index'));
        $response->assertSessionHas('success');

        $config->refresh();
        $this->assertEquals('500', $config->value);
        $this->assertEquals($this->superadmin->id, $config->updated_by);
    }

    public function test_superadmin_can_update_boolean_config(): void
    {
        $config = SystemConfig::factory()->boolean()->create(['value' => 'false']);

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => 'true',
        ]);

        $response->assertRedirect(route('admin.configs.index'));
        $config->refresh();
        $this->assertEquals('true', $config->value);
    }

    public function test_superadmin_can_update_string_config(): void
    {
        $config = SystemConfig::factory()->string()->create(['value' => 'old_value']);

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => 'new_value',
        ]);

        $response->assertRedirect(route('admin.configs.index'));
        $config->refresh();
        $this->assertEquals('new_value', $config->value);
    }

    public function test_superadmin_can_update_json_config(): void
    {
        $config = SystemConfig::factory()->json()->create();

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => '{"new_key": "new_value"}',
        ]);

        $response->assertRedirect(route('admin.configs.index'));
        $config->refresh();
        $this->assertEquals('{"new_key": "new_value"}', $config->value);
    }

    public function test_update_tracks_updated_by(): void
    {
        $config = SystemConfig::factory()->create([
            'key' => 'trial_duration_days',
            'value' => '14',
            'type' => 'integer',
            'updated_by' => null,
        ]);

        $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => '30',
        ]);

        $config->refresh();
        $this->assertEquals($this->superadmin->id, $config->updated_by);
        $this->assertNotNull($config->updated_at);
    }

    // --- Validation Tests ---

    public function test_integer_config_rejects_non_integer_value(): void
    {
        $config = SystemConfig::factory()->create([
            'key' => 'default_rate_limit_per_hour',
            'type' => 'integer',
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => 'not_a_number',
        ]);

        $response->assertSessionHasErrors('value');
    }

    public function test_integer_config_rejects_out_of_range_value(): void
    {
        $config = SystemConfig::factory()->create([
            'key' => 'default_rate_limit_per_hour',
            'value' => '200',
            'type' => 'integer',
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => '9999',
        ]);

        $response->assertSessionHasErrors('value');
        $config->refresh();
        $this->assertEquals('200', $config->value);
    }

    public function test_integer_config_rejects_below_minimum(): void
    {
        $config = SystemConfig::factory()->create([
            'key' => 'default_delay_min_seconds',
            'value' => '5',
            'type' => 'integer',
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => '0',
        ]);

        $response->assertSessionHasErrors('value');
    }

    public function test_boolean_config_rejects_invalid_value(): void
    {
        $config = SystemConfig::factory()->boolean()->create();

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => 'maybe',
        ]);

        $response->assertSessionHasErrors('value');
    }

    public function test_json_config_rejects_invalid_json(): void
    {
        $config = SystemConfig::factory()->json()->create();

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => 'not valid json{',
        ]);

        $response->assertSessionHasErrors('value');
    }

    public function test_update_requires_value(): void
    {
        $config = SystemConfig::factory()->create();

        $response = $this->actingAs($this->superadmin)->put(route('admin.configs.update', $config), [
            'value' => '',
        ]);

        $response->assertSessionHasErrors('value');
    }
}
