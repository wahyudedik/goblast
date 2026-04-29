<?php

namespace App\Policies;

use App\Models\Broadcast;
use App\Models\User;

class BroadcastPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view broadcasts belonging to their tenant
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Broadcast $broadcast): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $broadcast->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users with tenant can create broadcasts
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Broadcast $broadcast): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $broadcast->tenant_id;
    }

    /**
     * Determine whether the user can cancel the broadcast.
     */
    public function cancel(User $user, Broadcast $broadcast): bool
    {
        // Check tenant ownership
        if ($user->tenant_id !== $broadcast->tenant_id) {
            return false;
        }

        // Can only cancel broadcasts that are queued or running
        return in_array($broadcast->status, ['queued', 'running']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Broadcast $broadcast): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $broadcast->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Broadcast $broadcast): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $broadcast->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Broadcast $broadcast): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $broadcast->tenant_id;
    }
}
