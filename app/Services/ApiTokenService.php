<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Services\Contracts\ApiTokenServiceInterface;
use Illuminate\Support\Str;

class ApiTokenService implements ApiTokenServiceInterface
{
    /**
     * Generate a new API token for a tenant.
     *
     * @return array{token: string, apiToken: ApiToken}
     */
    public function generate(Tenant $tenant, string $name): array
    {
        // Generate a random token (64 characters)
        $token = Str::random(64);

        // Hash the token using SHA-256
        $tokenHash = $this->hashToken($token);

        // Store the hashed token in the database
        $apiToken = ApiToken::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'token_hash' => $tokenHash,
            'last_used_at' => null,
            'revoked_at' => null,
        ]);

        // Return both the plaintext token (only shown once) and the model
        return [
            'token' => $token,
            'apiToken' => $apiToken,
        ];
    }

    /**
     * Validate a token and return the associated ApiToken model.
     */
    public function validate(string $token): ?ApiToken
    {
        // Hash the provided token
        $tokenHash = $this->hashToken($token);

        // Find the token in the database
        $apiToken = ApiToken::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->first();

        return $apiToken;
    }

    /**
     * Revoke an API token (soft delete).
     */
    public function revoke(ApiToken $apiToken): void
    {
        $apiToken->update([
            'revoked_at' => now(),
        ]);
    }

    /**
     * Update the last_used_at timestamp for a token.
     */
    public function trackUsage(ApiToken $apiToken): void
    {
        $apiToken->update([
            'last_used_at' => now(),
        ]);
    }

    /**
     * Hash a token using SHA-256.
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
