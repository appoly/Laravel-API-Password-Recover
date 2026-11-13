<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Tests;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class IndexMigrationTest extends TestCase
{
    #[Test]
    public function optional_index_migration_is_idempotent_and_reversible(): void
    {
        $migration = require __DIR__.'/../database/optional-migrations/3030_05_05_090001_add_password_helper_key_index_to_users_table.php';

        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasIndex('users', 'users_password_helper_key_index'));

        $migration->down();
        $migration->down();
        $this->assertFalse(Schema::hasIndex('users', 'users_password_helper_key_index'));
    }
}
