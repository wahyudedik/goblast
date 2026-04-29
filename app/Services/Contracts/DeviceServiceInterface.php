<?php

namespace App\Services\Contracts;

use App\Exceptions\DeviceLimitExceededException;
use App\Exceptions\GatewayException;
use App\Models\Device;
use App\Models\Tenant;

interface DeviceServiceInterface
{
    /**
     * Request a new device connection and generate QR code.
     *
     * @throws DeviceLimitExceededException
     * @throws GatewayException
     */
    public function requestConnection(Tenant $tenant, string $deviceName): Device;

    /**
     * Confirm device connection after QR code is scanned.
     *
     * @throws GatewayException
     */
    public function confirmConnection(string $deviceId, string $sessionData): void;

    /**
     * Check the current connection status of a device.
     *
     * @return string One of: 'connected', 'disconnected', 'error'
     *
     * @throws GatewayException
     */
    public function checkConnectionStatus(Device $device): string;

    /**
     * Disconnect a device and cleanup session data.
     *
     * @throws GatewayException
     */
    public function disconnect(Device $device): void;

    /**
     * Check if tenant can add another device based on subscription plan.
     */
    public function canAddDevice(Tenant $tenant): bool;

    /**
     * Get QR code for device connection.
     *
     * @throws GatewayException
     */
    public function getQrCode(Device $device): string;
}
