<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendBulkRequest;
use App\Models\Device;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\Contracts\BroadcastServiceInterface;
use Illuminate\Http\JsonResponse;

class SendBulkController extends Controller
{
    public function __construct(
        private BroadcastServiceInterface $broadcastService,
    ) {}

    /**
     * Send bulk WhatsApp messages.
     *
     * POST /api/v1/send-bulk
     */
    public function __invoke(SendBulkRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        // Validate device belongs to tenant
        $device = Device::where('id', $request->validated('device_id'))
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $device) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => [
                    'device_id' => ['Device tidak ditemukan atau bukan milik tenant Anda.'],
                ],
            ], 422);
        }

        // Validate device is connected
        if ($device->status !== 'connected') {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => [
                    'device_id' => ['Device tidak dalam status terhubung. Status saat ini: '.$device->status],
                ],
            ], 422);
        }

        // Validate template belongs to tenant (if provided)
        $template = null;
        if ($request->validated('template_id')) {
            $template = Template::where('id', $request->validated('template_id'))
                ->where('tenant_id', $tenant->id)
                ->first();

            if (! $template) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => [
                        'template_id' => ['Template tidak ditemukan atau bukan milik tenant Anda.'],
                    ],
                ], 422);
            }
        }

        // Check subscription is active
        $subscription = $tenant->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (! $subscription) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Tidak ada langganan aktif. Silakan berlangganan terlebih dahulu.',
            ], 403);
        }

        $recipients = $request->validated('recipients');

        try {
            // Create broadcast from recipients array
            $broadcast = $this->broadcastService->createFromRecipients(
                tenant: $tenant,
                recipients: $recipients,
                device: $device,
                template: $template,
            );

            // Store the message on the broadcast if no template is used
            if (! $template && $request->validated('message')) {
                $broadcast->update(['message' => $request->validated('message')]);
            }

            // Dispatch the broadcast (creates message jobs)
            $this->broadcastService->dispatch($broadcast);

            return response()->json([
                'success' => true,
                'broadcast_id' => $broadcast->id,
                'total_recipients' => $broadcast->total_recipients,
                'status' => 'queued',
                'message' => 'Broadcast telah dimasukkan ke antrian',
            ], 202);
        } catch (QuotaExceededException $e) {
            return response()->json([
                'error' => 'Quota exceeded',
                'message' => 'Kuota pesan tidak mencukupi. Sisa kuota: '.$e->remaining.', dibutuhkan: '.$e->required,
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => [
                    'recipients' => [$e->getMessage()],
                ],
            ], 422);
        }
    }
}
