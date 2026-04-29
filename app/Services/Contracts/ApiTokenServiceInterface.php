<?php

namespace App\Services\Contracts;

use App\Models\ApiToken;
use App\Models\Tenant;

interface ApiTokenServiceInterface
{
    /**
     * Generate a new API token for a tenant.
     *
     * @return array{token: string, apiToken: ApiToken}
     */
    public function generate(Tenant $tenant, string $name): array;

    /**
     * Validate a token and return the associated ApiToken model.
     */
    public function validate(string $token): ?ApiToken;

    /**
     * Revoke an API token.
     */
    public function revoke(ApiToken $apiToken): void;

    /**
     * Update the last_used_at timestamp for a token.
     */
    public function trackUsage(ApiToken $apiToken): void;

    /**
     * Hash a token using SHA-256.
     */
    public function hashToken(string $token): string;
}
