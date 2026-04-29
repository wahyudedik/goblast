<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function it_displays_device_index_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['role' => 'admin']);
        $plan = Plan::factory()->create(['max_devices' => 2]);
        Subscription::factory()->for($tenant)->for($plan)->create(['status' => 'active']);

        Device::factory()->for($tenant)->count(2)->create();

        $response = $this->actingAs($user)->get(route('devices.index'));

        $response->assertOk();
        $response->assertViewIs('devices.index');
        $response->assertViewHas('devices');
        $response->assertViewHas('maxDevices', 2);
    }

    #[Test]
    public function it_displays_device_create_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['role' => 'admin']);
        $plan = Plan::factory()->create(['max_devices' => 2]);
        Subscription::factory()->for($tenant)->for($plan)->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('devices.create'));

        $response->assertOk();
        $response->assertViewIs('devices.create');
    }

    #[Test]
    public function it_redirects_when_device_limit_reached(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['role' => 'admin']);
        $plan = Plan::factory()->create(['max_devices' => 1]);
        Subscription::factory()->for($tenant)->for($plan)->create(['status' => 'active']);

        // Create one device (at limit)
        Device::factory()->for($tenant)->create(['status' => 'connected']);

        $response = $this->actingAs($user)->get(route('devices.create'));

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_displays_device_show_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['role' => 'admin']);
        $device = Device::factory()->for($tenant)->create();

        $response = $this->actingAs($user)->get(route('devices.show', $device));

        $response->assertOk();
        $response->assertViewIs('devices.show');
        $response->assertViewHas('device');
    }

    #[Test]
    public function it_prevents_viewing_other_tenant_device(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user = User::factory()->for($tenant1)->create(['role' => 'admin']);
        $device = Device::factory()->for($tenant2)->create();

        $response = $this->actingAs($user)->get(route('devices.show', $device));

        $response->assertForbidden();
    }

    #[Test]
    public function it_displays_device_edit_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['role' => 'admin']);
        $device = Device::factory()->for($tenant)->create();

        $response = $this->actingAs($user)->get(route('devices.edit', $device));

        $response->assertOk();
        $response->assertViewIs('devices.edit');
        $response->assertViewHas('device');
    }

    #[Test]
    public function it_updates_device_name(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['role' => 'admin']);
        $device = Device::factory()->for($tenant)->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('devices.update', $device), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('devices.show', $device));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'New Name',
        ]);
    }
}
