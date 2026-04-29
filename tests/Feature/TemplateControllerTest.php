<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
    }

    public function test_can_view_templates_index(): void
    {
        Template::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->get(route('templates.index'));

        $response->assertOk();
        $response->assertViewIs('templates.index');
        $response->assertViewHas('templates');
    }

    public function test_can_filter_templates_by_type(): void
    {
        Template::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'notification']);
        Template::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'promo']);

        $response = $this->actingAs($this->user)->get(route('templates.index', ['type' => 'notification']));

        $response->assertOk();
        $response->assertSee('notification');
    }

    public function test_can_view_create_template_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('templates.create'));

        $response->assertOk();
        $response->assertViewIs('templates.create');
    }

    public function test_can_create_template(): void
    {
        $data = [
            'name' => 'Test Template',
            'type' => 'notification',
            'content' => 'Hello {name}, your order {order_id} is ready!',
        ];

        $response = $this->actingAs($this->user)->post(route('templates.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('templates', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Template',
            'type' => 'notification',
        ]);

        $template = Template::where('name', 'Test Template')->first();
        $this->assertEquals(['name', 'order_id'], $template->variables);
    }

    public function test_template_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('templates.store'), []);

        $response->assertSessionHasErrors(['name', 'type', 'content']);
    }

    public function test_template_content_cannot_exceed_4096_characters(): void
    {
        $data = [
            'name' => 'Test Template',
            'type' => 'notification',
            'content' => str_repeat('a', 4097),
        ];

        $response = $this->actingAs($this->user)->post(route('templates.store'), $data);

        $response->assertSessionHasErrors(['content']);
    }

    public function test_can_view_template_details(): void
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->get(route('templates.show', $template));

        $response->assertOk();
        $response->assertViewIs('templates.show');
        $response->assertViewHas('template');
        $response->assertSee($template->name);
    }

    public function test_can_view_edit_template_form(): void
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->get(route('templates.edit', $template));

        $response->assertOk();
        $response->assertViewIs('templates.edit');
        $response->assertViewHas('template');
    }

    public function test_can_update_template(): void
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $data = [
            'name' => 'Updated Template',
            'type' => 'promo',
            'content' => 'New content with {variable}',
        ];

        $response = $this->actingAs($this->user)->put(route('templates.update', $template), $data);

        $response->assertRedirect(route('templates.show', $template));
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'name' => 'Updated Template',
            'type' => 'promo',
        ]);

        $template->refresh();
        $this->assertEquals(['variable'], $template->variables);
    }

    public function test_can_delete_template(): void
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->delete(route('templates.destroy', $template));

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    public function test_cannot_delete_template_used_by_active_reminder(): void
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);
        $device = $this->tenant->devices()->create([
            'name' => 'Test Device',
            'gateway_device_id' => 'test-device-id',
            'status' => 'connected',
        ]);

        $template->reminders()->create([
            'tenant_id' => $this->tenant->id,
            'device_id' => $device->id,
            'name' => 'Test Reminder',
            'type' => 'spp_due',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->delete(route('templates.destroy', $template));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('templates', ['id' => $template->id]);
    }

    public function test_can_duplicate_template(): void
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Template',
        ]);

        $response = $this->actingAs($this->user)->post(route('templates.duplicate', $template));

        $response->assertRedirect();
        $this->assertDatabaseHas('templates', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Template (Copy)',
            'type' => $template->type,
            'content' => $template->content,
        ]);
    }
}
