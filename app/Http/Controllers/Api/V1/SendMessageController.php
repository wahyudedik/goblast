<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendMessageRequest;
use App\Models\Device;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\Contracts\MessageServiceInterface;
use Illuminate\Http\JsonResponse;

class SendMessageController extends Controller
{
    public function __construct(
        private MessageServiceInterface $messageService,
    ) {}

    /**
     * Send a single WhatsApp message.
     *
     * POST /api/v1/send-message
     */
    public function __invoke(SendMessageRequest $request): JsonResponse
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

        // Determine message content
        $message = $request->validated('message') ?? '';

        try {
            $messageLog = $this->messageService->sendSingle(
                device: $device,
                to: $request->validated('to'),
                message: $message,
                template: $template,
            );

            return response()->json([
                'success' => true,
                'job_id' => $messageLog->job_id,
                'status' => 'queued',
                'message' => 'Pesan telah dimasukkan ke antrian',
            ], 202);
        } catch (QuotaExceededException $e) {
            return response()->json([
                'error' => 'Quota exceeded',
                'message' => 'Kuota pesan telah habis. Sisa kuota: '.$e->remaining,
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => [
                    'to' => [$e->getMessage()],
                ],
            ], 422);
        }
    }
}
