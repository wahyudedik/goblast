<?php

namespace App\Services\Contracts;

use App\Exceptions\GatewayException;
use App\Services\ValueObjects\GatewayResponse;

interface GatewayClientInterface
{
    /**
     * Send a message through the gateway.
     *
     * @throws GatewayException
     */
    public function sendMessage(string $sessionName, string $to, string $message): GatewayResponse;

    /**
     * Request a QR code for device connection.
     *
     * @throws GatewayException
     */
    public function getQrCode(string $sessionName): string;

    /**
     * Get the current connection status of a session.
     *
     * @return string One of: 'connected', 'disconnected', 'error'
     */
    public function getConnectionStatus(string $sessionName): string;

    /**
     * Disconnect a device from the gateway.
     *
     * @throws GatewayException
     */
    public function disconnectDevice(string $sessionName): void;

    /**
     * Restart a gateway instance.
     *
     * @throws GatewayException
     */
    public function restartInstance(string $sessionName): void;
}
