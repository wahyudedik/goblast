<?php

namespace App\Services\Contracts;

use App\Models\User;

interface GoogleAuthServiceInterface
{
    /**
     * Find or create a user based on Google OAuth profile data.
     *
     * If the email exists, updates google_id and google_avatar.
     * If the email doesn't exist, creates a new Tenant, User, and Subscription.
     *
     * @param  array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     avatar: string|null,
     * }  $googleUser
     */
    public function findOrCreateUser(array $googleUser): User;
}
