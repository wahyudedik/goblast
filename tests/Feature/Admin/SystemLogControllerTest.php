<?php

namespace Tests\Feature\Admin;

use App\Models\SystemLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SystemLogControllerTest extends TestCase
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

    public function test_non_superadmin_cannot_access_log_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.logs.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_log_index(): void
    {
        $response = $this->get(route('admin.logs.index'));

        $response->assertRedirect(route('login'));
    }

    // --- Index Tests ---

    public function test_superadmin_can_view_log_index(): void
    {
        SystemLog::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index'));

        $response->assertOk();
        $response->assertViewIs('admin.logs.index');
        $response->assertViewHas('logs');
    }

    public function test_log_index_displays_all_columns(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $log = SystemLog::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'device.connected',
            'severity' => 'info',
            'message' => 'Device successfully connected',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index'));

        $response->assertOk();
        $response->assertSee('Test Tenant');
        $response->assertSee('device.connected');
        $response->assertSee('Info');
        $response->assertSee('Device successfully connected');
    }

    public function test_log_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index'));

        $response->assertOk();
        $response->assertSee('Tidak ada log');
    }

    public function test_log_index_filters_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant Alpha']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant Beta']);

        SystemLog::factory()->create(['tenant_id' => $tenant1->id, 'message' => 'Alpha log message']);
        SystemLog::factory()->create(['tenant_id' => $tenant2->id, 'message' => 'Beta log message']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index', ['tenant_id' => $tenant1->id]));

        $response->assertOk();
        $response->assertSee('Alpha log message');
        $response->assertDontSee('Beta log message');
    }

    public function test_log_index_filters_by_type(): void
    {
        SystemLog::factory()->create(['type' => 'device.connected', 'message' => 'Device connected log']);
        SystemLog::factory()->create(['type' => 'quota.exhausted', 'message' => 'Quota exhausted log']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index', ['type' => 'device.connected']));

        $response->assertOk();
        $response->assertSee('Device connected log');
        $response->assertDontSee('Quota exhausted log');
    }

    public function test_log_index_filters_by_severity(): void
    {
        SystemLog::factory()->create(['severity' => 'critical', 'message' => 'Critical log entry']);
        SystemLog::factory()->create(['severity' => 'info', 'message' => 'Info log entry']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index', ['severity' => 'critical']));

        $response->assertOk();
        $response->assertSee('Critical log entry');
        $response->assertDontSee('Info log entry');
    }

    public function test_log_index_filters_by_date_range(): void
    {
        SystemLog::factory()->create([
            'message' => 'Old log entry',
            'created_at' => now()->subDays(30),
        ]);
        SystemLog::factory()->create([
            'message' => 'Recent log entry',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index', [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Recent log entry');
        $response->assertDontSee('Old log entry');
    }

    public function test_log_index_filters_by_keyword(): void
    {
        SystemLog::factory()->create(['message' => 'Gateway connection failed']);
        SystemLog::factory()->create(['message' => 'Quota reset completed']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.index', ['keyword' => 'Gateway']));

        $response->assertOk();
        $response->assertSee('Gateway connection failed');
        $response->assertDontSee('Quota reset completed');
    }

    // --- Show Tests ---

    public function test_superadmin_can_view_log_details(): void
    {
        $log = SystemLog::factory()->create([
            'type' => 'device.connected',
            'message' => 'Device XYZ connected successfully',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.show', $log));

        $response->assertOk();
        $response->assertViewIs('admin.logs.show');
        $response->assertViewHas('log');
        $response->assertSee('device.connected');
        $response->assertSee('Device XYZ connected successfully');
    }

    public function test_show_displays_context_when_present(): void
    {
        $log = SystemLog::factory()->withContext()->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.show', $log));

        $response->assertOk();
        $response->assertSee('Context Information');
    }

    public function test_show_hides_context_section_when_null(): void
    {
        $log = SystemLog::factory()->create(['context' => null]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.show', $log));

        $response->assertOk();
        $response->assertDontSee('<h3 class="text-base font-semibold text-gray-900">Context Information</h3>', false);
    }

    public function test_show_displays_related_logs(): void
    {
        $log = SystemLog::factory()->create([
            'type' => 'device.connected',
            'created_at' => now(),
        ]);

        // Related log (same type, within 1 hour)
        SystemLog::factory()->create([
            'type' => 'device.connected',
            'message' => 'Related log entry',
            'created_at' => now()->subMinutes(30),
        ]);

        // Unrelated log (different type)
        SystemLog::factory()->create([
            'type' => 'quota.exhausted',
            'message' => 'Unrelated log entry',
            'created_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.show', $log));

        $response->assertOk();
        $response->assertSee('Related Logs');
        $response->assertSee('Related log entry');
        $response->assertDontSee('Unrelated log entry');
    }

    public function test_show_displays_tenant_link(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Linked Tenant']);
        $log = SystemLog::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.show', $log));

        $response->assertOk();
        $response->assertSee('Linked Tenant');
    }

    public function test_show_displays_global_label_when_no_tenant(): void
    {
        $log = SystemLog::factory()->create(['tenant_id' => null]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.show', $log));

        $response->assertOk();
        $response->assertSee('Sistem (global)');
    }

    public function test_non_superadmin_cannot_view_log_details(): void
    {
        $log = SystemLog::factory()->create();

        $response = $this->actingAs($this->regularUser)->get(route('admin.logs.show', $log));

        $response->assertForbidden();
    }

    // --- Export Tests ---

    public function test_superadmin_can_export_logs_to_csv(): void
    {
        SystemLog::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.export'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
        $response->assertHeader('Content-Disposition');
    }

    public function test_export_applies_filters(): void
    {
        SystemLog::factory()->create(['severity' => 'critical', 'message' => 'Critical export log']);
        SystemLog::factory()->create(['severity' => 'info', 'message' => 'Info export log']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.export', ['severity' => 'critical']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Critical export log', $content);
        $this->assertStringNotContainsString('Info export log', $content);
    }

    public function test_export_contains_csv_headers(): void
    {
        SystemLog::factory()->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.logs.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('ID', $content);
        $this->assertStringContainsString('Timestamp', $content);
        $this->assertStringContainsString('Tenant', $content);
        $this->assertStringContainsString('Severity', $content);
        $this->assertStringContainsString('Message', $content);
    }

    public function test_non_superadmin_cannot_export_logs(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.logs.export'));

        $response->assertForbidden();
    }
}
