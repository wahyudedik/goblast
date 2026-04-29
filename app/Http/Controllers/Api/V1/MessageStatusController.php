<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageStatusController extends Controller
{
    /**
     * Check message delivery status by job_id.
     *
     * GET /api/v1/message-status/{job_id}
     */
    public function __invoke(Request $request, string $jobId): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        $messageLog = MessageLog::where('job_id', $jobId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $messageLog) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Job tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'job_id' => $messageLog->job_id,
            'status' => $messageLog->status,
            'recipient' => $messageLog->recipient,
            'sent_at' => $messageLog->sent_at?->toIso8601String(),
            'failed_at' => $messageLog->failed_at?->toIso8601String(),
            'error_message' => $messageLog->error_message,
            'attempts' => $messageLog->attempts,
            'message' => match ($messageLog->status) {
                'sent' => 'Pesan berhasil terkirim',
                'failed' => 'Pesan gagal terkirim',
                'pending' => 'Pesan sedang menunggu dalam antrian',
                'cancelled' => 'Pesan dibatalkan',
                'retrying' => 'Pesan sedang dicoba ulang',
                default => 'Status tidak diketahui',
            },
        ]);
    }
}
