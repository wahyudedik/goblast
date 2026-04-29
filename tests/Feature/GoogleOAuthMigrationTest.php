<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleOAuthMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_users_table_has_google_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('users', 'google_id'),
            'Column google_id does not exist on users table'
        );
    }

    public function test_users_table_has_google_avatar_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('users', 'google_avatar'),
            'Column google_avatar does not exist on users table'
        );
    }

    public function test_google_id_column_is_nullable(): void
    {
        $columns = Schema::getColumns('users');
        $googleIdColumn = collect($columns)->firstWhere('name', 'google_id');

        $this->assertNotNull($googleIdColumn, 'Column google_id not found');
        $this->assertTrue($googleIdColumn['nullable'], 'Column google_id should be nullable');
    }

    public function test_google_avatar_column_is_nullable(): void
    {
        $columns = Schema::getColumns('users');
        $googleAvatarColumn = collect($columns)->firstWhere('name', 'google_avatar');

        $this->assertNotNull($googleAvatarColumn, 'Column google_avatar not found');
        $this->assertTrue($googleAvatarColumn['nullable'], 'Column google_avatar should be nullable');
    }

    public function test_password_column_is_nullable(): void
    {
        $columns = Schema::getColumns('users');
        $passwordColumn = collect($columns)->firstWhere('name', 'password');

        $this->assertNotNull($passwordColumn, 'Column password not found');
        $this->assertTrue($passwordColumn['nullable'], 'Column password should be nullable');
    }

    public function test_google_id_has_unique_index(): void
    {
        $indexes = Schema::getIndexes('users');
        $googleIdIndex = collect($indexes)->first(function ($index) {
            return in_array('google_id', $index['columns']) && $index['unique'];
        });

        $this->assertNotNull($googleIdIndex, 'Unique index on google_id column not found');
    }
}
