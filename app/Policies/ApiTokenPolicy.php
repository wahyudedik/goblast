<?php

namespace App\Policies;

use App\Models\ApiToken;
use App\Models\User;

class ApiTokenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view API tokens belonging to their tenant
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ApiToken $apiToken): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $apiToken->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users with tenant can create API tokens
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ApiToken $apiToken): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $apiToken->tenant_id;
    }

    /**
     * Determine whether the user can revoke the API token.
     */
    public function revoke(User $user, ApiToken $apiToken): bool
    {
        // Check tenant ownership
        if ($user->tenant_id !== $apiToken->tenant_id) {
            return false;
        }

        // Can only revoke tokens that are not already revoked
        return $apiToken->revoked_at === null;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApiToken $apiToken): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $apiToken->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ApiToken $apiToken): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $apiToken->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ApiToken $apiToken): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $apiToken->tenant_id;
    }
}
