<?php

namespace Tests\Unit\Services;

use App\Exceptions\QuotaExceededException;
use App\Models\Broadcast;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\BroadcastService;
use App\Services\Contracts\MessageServiceInterface;
use App\Services\Contracts\QuotaServiceInterface;
use App\Services\ValueObjects\BroadcastProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BroadcastService $service;

    protected QuotaServiceInterface $quotaService;

    protected MessageServiceInterface $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = $this->createMock(QuotaServiceInterface::class);
        $this->messageService = $this->createMock(MessageServiceInterface::class);
        $this->service = new BroadcastService($this->quotaService, $this->messageService);

        Storage::fake('local');
    }

    #[Test]
    public function it_creates_broadcast_from_valid_csv(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        // Create CSV content
        $csvContent = "phone\n6281234567890\n6281234567891\n6281234567892";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $broadcast = $this->service->createFromCsv($tenant, $file, $device, $template);

        $this->assertInstanceOf(Broadcast::class, $broadcast);
        $this->assertEquals($tenant->id, $broadcast->tenant_id);
        $this->assertEquals($device->id, $broadcast->device_id);
        $this->assertEquals($template->id, $broadcast->template_id);
        $this->assertEquals(3, $broadcast->total_recipients);
        $this->assertEquals('csv', $broadcast->source_type);
        $this->assertEquals('draft', $broadcast->status);
        $this->assertNotNull($broadcast->csv_path);
    }

    #[Test]
    public function it_rejects_csv_file_exceeding_5mb(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        // Create a file larger than 5MB
        $file = UploadedFile::fake()->create('large.csv', 6000); // 6MB

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CSV file size exceeds 5MB limit');

        $this->service->createFromCsv($tenant, $file, $device, $template);
    }

    #[Test]
    public function it_rejects_non_csv_file(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File must be a CSV file');

        $this->service->createFromCsv($tenant, $file, $device, $template);
    }

    #[Test]
    public function it_skips_invalid_phone_numbers_in_csv(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        // CSV with valid and invalid numbers
        $csvContent = "phone\n6281234567890\ninvalid\n123\n6281234567891";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $broadcast = $this->service->createFromCsv($tenant, $file, $device, $template);

        // Should only count valid phone numbers
        $this->assertEquals(2, $broadcast->total_recipients);
    }

    #[Test]
    public function it_removes_duplicate_phone_numbers_from_csv(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        // CSV with duplicate numbers
        $csvContent = "phone\n6281234567890\n6281234567890\n6281234567891";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $broadcast = $this->service->createFromCsv($tenant, $file, $device, $template);

        // Should remove duplicates
        $this->assertEquals(2, $broadcast->total_recipients);
    }

    #[Test]
    public function it_creates_broadcast_from_recipients_array(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        $recipients = ['6281234567890', '6281234567891', '6281234567892'];

        $broadcast = $this->service->createFromRecipients($tenant, $recipients, $device, $template);

        $this->assertInstanceOf(Broadcast::class, $broadcast);
        $this->assertEquals(3, $broadcast->total_recipients);
        $this->assertEquals('database', $broadcast->source_type);
        // Recipients are stored in a CSV file for consistency with dispatch
        $this->assertNotNull($broadcast->csv_path);
        $this->assertStringStartsWith('broadcasts/recipients/', $broadcast->csv_path);
    }

    #[Test]
    public function it_rejects_empty_recipients_array(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipients array cannot be empty');

        $this->service->createFromRecipients($tenant, [], $device, $template);
    }

    #[Test]
    public function it_filters_invalid_recipients_from_array(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        $recipients = ['6281234567890', 'invalid', '123', '6281234567891'];

        $broadcast = $this->service->createFromRecipients($tenant, $recipients, $device, $template);

        // Should only count valid phone numbers
        $this->assertEquals(2, $broadcast->total_recipients);
    }

    #[Test]
    public function it_dispatches_broadcast_with_sufficient_quota(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create(['content' => 'Test message']);

        // Create CSV and broadcast
        $csvContent = "phone\n6281234567890\n6281234567891";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);
        $broadcast = $this->service->createFromCsv($tenant, $file, $device, $template);

        // Mock quota service
        $this->quotaService->method('getRemainingQuota')->willReturn(100);
        $this->quotaService->method('isUnlimited')->willReturn(false);
        $this->quotaService->expects($this->exactly(2))->method('decrement');

        // Mock message service
        $this->messageService->method('renderTemplate')->willReturn('Test message');
        $this->messageService->expects($this->exactly(2))->method('dispatchJob');

        $this->service->dispatch($broadcast);

        $broadcast->refresh();
        $this->assertEquals('running', $broadcast->status);
        $this->assertNotNull($broadcast->started_at);

        // Check message logs created
        $this->assertEquals(2, MessageLog::where('broadcast_id', $broadcast->id)->count());
    }

    #[Test]
    public function it_throws_exception_when_quota_exhausted(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        $csvContent = "phone\n6281234567890\n6281234567891";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);
        $broadcast = $this->service->createFromCsv($tenant, $file, $device, $template);

        // Mock quota exhausted
        $this->quotaService->method('getRemainingQuota')->willReturn(0);
        $this->quotaService->method('isUnlimited')->willReturn(false);

        $this->expectException(QuotaExceededException::class);

        $this->service->dispatch($broadcast);
    }

    #[Test]
    public function it_handles_partial_dispatch_when_quota_insufficient(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create(['content' => 'Test message']);

        // Create broadcast with 3 recipients
        $csvContent = "phone\n6281234567890\n6281234567891\n6281234567892";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);
        $broadcast = $this->service->createFromCsv($tenant, $file, $device, $template);

        // Mock quota: only 2 remaining
        $this->quotaService->method('getRemainingQuota')->willReturn(2);
        $this->quotaService->method('isUnlimited')->willReturn(false);
        $this->quotaService->expects($this->exactly(2))->method('decrement');

        // Mock message service
        $this->messageService->method('renderTemplate')->willReturn('Test message');
        $this->messageService->expects($this->exactly(2))->method('dispatchJob');

        $this->service->dispatch($broadcast);

        // Should only create 2 message logs (partial dispatch)
        $this->assertEquals(2, MessageLog::where('broadcast_id', $broadcast->id)->count());
    }

    #[Test]
    public function it_dispatches_with_random_delay_between_5_and_10_seconds(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create(['content' => 'Test message']);

        $csvContent = "phone\n6281234567890";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);
        $broadcast = $this->service->createFromCsv($tenant, $file, $device, $template);

        $this->quotaService->method('getRemainingQuota')->willReturn(100);
        $this->quotaService->method('isUnlimited')->willReturn(false);
        $this->messageService->method('renderTemplate')->willReturn('Test message');

        // Verify delay is between 5 and 10 seconds
        $this->messageService->expects($this->once())
            ->method('dispatchJob')
            ->with(
                $this->anything(),
                $this->logicalAnd(
                    $this->greaterThanOrEqual(5),
                    $this->lessThanOrEqual(10)
                )
            );

        $this->service->dispatch($broadcast);
    }

    #[Test]
    public function it_cancels_broadcast_and_updates_status(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();
        $broadcast = Broadcast::factory()->for($tenant)->for($device)->for($template)->create([
            'status' => 'running',
        ]);

        // Create some pending message logs
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->create(['status' => 'pending']);
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->create(['status' => 'pending']);
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->create(['status' => 'sent']);

        $this->service->cancel($broadcast);

        $broadcast->refresh();
        $this->assertEquals('cancelled', $broadcast->status);
        $this->assertNotNull($broadcast->completed_at);

        // Check pending messages are cancelled
        $this->assertEquals(2, MessageLog::where('broadcast_id', $broadcast->id)
            ->where('status', 'cancelled')
            ->count());

        // Sent message should remain sent
        $this->assertEquals(1, MessageLog::where('broadcast_id', $broadcast->id)
            ->where('status', 'sent')
            ->count());
    }

    #[Test]
    public function it_gets_broadcast_progress(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();
        $broadcast = Broadcast::factory()->for($tenant)->for($device)->for($template)->create([
            'total_recipients' => 10,
            'status' => 'running',
        ]);

        // Create message logs with different statuses
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(5)->create(['status' => 'sent']);
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(2)->create(['status' => 'failed']);
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(3)->create(['status' => 'pending']);

        $progress = $this->service->getProgress($broadcast);

        $this->assertInstanceOf(BroadcastProgress::class, $progress);
        $this->assertEquals(10, $progress->total);
        $this->assertEquals(5, $progress->sent);
        $this->assertEquals(2, $progress->failed);
        $this->assertEquals(3, $progress->pending);
        $this->assertEquals(70.0, $progress->percentage); // (5+2)/10 * 100
    }

    #[Test]
    public function it_marks_broadcast_as_completed_when_all_messages_processed(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();
        $broadcast = Broadcast::factory()->for($tenant)->for($device)->for($template)->create([
            'total_recipients' => 5,
            'status' => 'running',
        ]);

        // All messages processed (no pending)
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(3)->create(['status' => 'sent']);
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(2)->create(['status' => 'failed']);

        $this->service->getProgress($broadcast);

        $broadcast->refresh();
        $this->assertEquals('completed', $broadcast->status);
        $this->assertNotNull($broadcast->completed_at);
    }

    #[Test]
    public function it_normalizes_phone_numbers_with_plus_prefix(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        $recipients = ['+6281234567890', '6281234567891'];

        $broadcast = $this->service->createFromRecipients($tenant, $recipients, $device, $template);

        $this->assertEquals(2, $broadcast->total_recipients);
    }

    #[Test]
    public function it_normalizes_phone_numbers_with_spaces_and_dashes(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create();
        $template = Template::factory()->for($tenant)->create();

        $recipients = ['628 1234 567890', '628-1234-567891'];

        $broadcast = $this->service->createFromRecipients($tenant, $recipients, $device, $template);

        $this->assertEquals(2, $broadcast->total_recipients);
    }
}
