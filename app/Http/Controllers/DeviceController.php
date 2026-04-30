<?php

namespace App\Http\Controllers;

use App\Exceptions\DeviceLimitExceededException;
use App\Exceptions\GatewayException;
use App\Models\Device;
use App\Services\Contracts\DeviceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceServiceInterface $deviceService,
    ) {}

    /**
     * Display a listing of devices.
     */
    public function index()
    {
        $tenant = Auth::user()->tenant;

        $devices = $tenant->devices()
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active subscription to show device limit
        $activeSubscription = $tenant->subscriptions()
            ->where('status', 'active')
            ->with('plan')
            ->first();

        $maxDevices = $activeSubscription?->plan->max_devices ?? 1;
        $currentDeviceCount = $devices->whereIn('status', ['connected', 'pending'])->count();
        $canAddDevice = $this->deviceService->canAddDevice($tenant);

        return view('devices.index', [
            'devices' => $devices,
            'maxDevices' => $maxDevices,
            'currentDeviceCount' => $currentDeviceCount,
            'canAddDevice' => $canAddDevice,
        ]);
    }

    /**
     * Show the form for creating a new device connection.
     */
    public function create()
    {
        $tenant = Auth::user()->tenant;

        // Check rate limit status to prevent UI bypass
        $key = md5('device-creation'.'tenant:'.$tenant->id);
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return redirect()->route('devices.index')
                ->with('rate_limited', true)
                ->with('retry_after', $retryAfter)
                ->with('error', 'Terlalu banyak percobaan. Silakan tunggu sebelum mencoba lagi.');
        }

        if (! $this->deviceService->canAddDevice($tenant)) {
            return redirect()->route('devices.index')
                ->with('error', 'Device limit reached. Please upgrade your plan to add more devices.');
        }

        return view('devices.create');
    }

    /**
     * Store a newly created device connection request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $tenant = Auth::user()->tenant;

        try {
            $device = $this->deviceService->requestConnection($tenant, $request->input('name'));

            return redirect()->route('devices.connect', $device)
                ->with('success', 'Device created. Please scan the QR code to connect.');
        } catch (DeviceLimitExceededException $e) {
            return redirect()->route('devices.index')
                ->with('error', $e->getMessage());
        } catch (GatewayException $e) {
            return redirect()->route('devices.index')
                ->with('error', 'Failed to connect to gateway: '.$e->getMessage());
        }
    }

    /**
     * Display the QR code connection page.
     */
    public function connect(Device $device)
    {
        Gate::authorize('view', $device);

        if ($device->status !== 'pending') {
            return redirect()->route('devices.show', $device)
                ->with('info', 'Device is already connected.');
        }

        return view('devices.connect', [
            'device' => $device,
        ]);
    }

    /**
     * Display the specified device.
     */
    public function show(Device $device)
    {
        Gate::authorize('view', $device);

        // Load device usage statistics
        $device->load(['messageLogs' => function ($query) {
            $query->selectRaw('device_id, status, COUNT(*) as count')
                ->groupBy('device_id', 'status');
        }]);

        $totalMessages = $device->messageLogs->sum('count');
        $sentMessages = $device->messageLogs->where('status', 'sent')->sum('count');
        $failedMessages = $device->messageLogs->where('status', 'failed')->sum('count');

        return view('devices.show', [
            'device' => $device,
            'totalMessages' => $totalMessages,
            'sentMessages' => $sentMessages,
            'failedMessages' => $failedMessages,
        ]);
    }

    /**
     * Show the form for editing the device name.
     */
    public function edit(Device $device)
    {
        Gate::authorize('update', $device);

        return view('devices.edit', [
            'device' => $device,
        ]);
    }

    /**
     * Update the device name.
     */
    public function update(Request $request, Device $device)
    {
        Gate::authorize('update', $device);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $device->update([
            'name' => $request->input('name'),
        ]);

        return redirect()->route('devices.show', $device)
            ->with('success', 'Device name updated successfully.');
    }

    /**
     * Disconnect the device.
     */
    public function disconnect(Device $device)
    {
        Gate::authorize('update', $device);

        try {
            $this->deviceService->disconnect($device);

            return redirect()->route('devices.index')
                ->with('success', 'Device disconnected successfully.');
        } catch (GatewayException $e) {
            return redirect()->route('devices.index')
                ->with('warning', 'Device disconnected locally, but gateway reported an error: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified device.
     */
    public function destroy(Device $device)
    {
        Gate::authorize('delete', $device);

        // Disconnect first if connected
        if (in_array($device->status, ['connected', 'pending'])) {
            try {
                $this->deviceService->disconnect($device);
            } catch (GatewayException $e) {
                // Log but continue with deletion
                \Log::warning('Gateway disconnect failed during device deletion', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $device->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Device deleted successfully.');
    }

    /**
     * Check the connection status of a device (AJAX endpoint).
     */
    public function checkStatus(Device $device)
    {
        Gate::authorize('view', $device);

        try {
            $status = $this->deviceService->checkConnectionStatus($device);

            return response()->json([
                'success' => true,
                'status' => $status,
                'last_seen_at' => $device->fresh()->last_seen_at?->toIso8601String(),
            ]);
        } catch (GatewayException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR code for device connection (AJAX endpoint).
     */
    public function getQrCode(Device $device)
    {
        Gate::authorize('view', $device);

        try {
            $qrCode = $this->deviceService->getQrCode($device);

            return response()->json([
                'success' => true,
                'qr_code' => $qrCode,
            ]);
        } catch (GatewayException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get rate limit status for device creation (AJAX endpoint).
     */
    public function rateLimitStatus(): JsonResponse
    {
        $tenant = Auth::user()->tenant;
        $key = md5('device-creation'.'tenant:'.$tenant->id);
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        $isLimited = RateLimiter::tooManyAttempts($key, $maxAttempts);
        $attempts = RateLimiter::attempts($key);
        $availableIn = $isLimited ? RateLimiter::availableIn($key) : 0;

        return response()->json([
            'is_limited' => $isLimited,
            'remaining_attempts' => max(0, $maxAttempts - $attempts),
            'retry_after' => $availableIn,
        ]);
    }
}
