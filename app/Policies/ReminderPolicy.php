<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;

class ReminderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Reminder $reminder): bool
    {
        return $user->tenant_id === $reminder->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Reminder $reminder): bool
    {
        return $user->tenant_id === $reminder->tenant_id;
    }

    public function delete(User $user, Reminder $reminder): bool
    {
        return $user->tenant_id === $reminder->tenant_id;
    }
}
