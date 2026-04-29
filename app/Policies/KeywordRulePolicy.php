<?php

namespace App\Policies;

use App\Models\KeywordRule;
use App\Models\User;

class KeywordRulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view keyword rules belonging to their tenant
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KeywordRule $keywordRule): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $keywordRule->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users with tenant can create keyword rules
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, KeywordRule $keywordRule): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $keywordRule->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KeywordRule $keywordRule): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $keywordRule->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, KeywordRule $keywordRule): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $keywordRule->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, KeywordRule $keywordRule): bool
    {
        // Check tenant ownership
        return $user->tenant_id === $keywordRule->tenant_id;
    }
}
