<?php

namespace App\Policies;

use App\Models\MessageLog;
use App\Models\User;

class MessageLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view message logs belonging to their tenant
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MessageLog $messageLog): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $messageLog->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Message logs are created by the system, not directly by users
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MessageLog $messageLog): bool
    {
        // Check tenant ownership (for retry functionality)
        return $user->tenant_id === $messageLog->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MessageLog $messageLog): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $messageLog->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MessageLog $messageLog): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $messageLog->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MessageLog $messageLog): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $messageLog->tenant_id;
    }
}
