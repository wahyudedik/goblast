<?php

namespace App\Services\Contracts;

use App\Exceptions\GatewayException;
use App\Services\ValueObjects\BaileysResponse;

interface BaileysGatewayClientInterface
{
    /**
     * Send a message through Baileys Gateway.
     *
     * @throws GatewayException
     */
    public function sendMessage(string $deviceId, string $to, string $message): BaileysResponse;

    /**
     * Request a QR code for device connection.
     *
     * @throws GatewayException
     */
    public function getQrCode(string $deviceId): string;

    /**
     * Get the current connection status of a device.
     *
     * @return string One of: 'connected', 'disconnected', 'error'
     *
     * @throws GatewayException
     */
    public function getConnectionStatus(string $deviceId): string;

    /**
     * Disconnect a device from Baileys Gateway.
     *
     * @throws GatewayException
     */
    public function disconnectDevice(string $deviceId): void;

    /**
     * Restart a Baileys Gateway instance.
     *
     * @throws GatewayException
     */
    public function restartInstance(string $instanceId): void;
}
