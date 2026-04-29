<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

class TemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view templates belonging to their tenant
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Template $template): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $template->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users with tenant can create templates
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Template $template): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $template->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Template $template): bool
    {
        // Check tenant ownership
        if ($user->tenant_id !== $template->tenant_id) {
            return false;
        }

        // Prevent delete if template is used by active reminders
        $hasActiveReminders = $template->reminders()
            ->where('is_active', true)
            ->exists();

        return ! $hasActiveReminders;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Template $template): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $template->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Template $template): bool
    {
        // Check tenant ownership
        if ($user->tenant_id !== $template->tenant_id) {
            return false;
        }

        // Prevent delete if template is used by active reminders
        $hasActiveReminders = $template->reminders()
            ->where('is_active', true)
            ->exists();

        return ! $hasActiveReminders;
    }
}
