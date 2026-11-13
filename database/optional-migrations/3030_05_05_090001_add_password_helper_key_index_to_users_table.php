<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Optional. Published with:
 *   php artisan vendor:publish --tag=api-password-helper-index-migration
 *
 * Not loaded automatically, because indexing a large users table can be slow
 * or lock it. Review before running in production.
 *
 * Dated after the column migration (3030_05_05_090000) so a fresh install
 * creates the column first.
 */
return new class extends Migration
{
    private const INDEX = 'users_password_helper_key_index';

    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password_helper_key') || Schema::hasIndex('users', self::INDEX)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index('password_helper_key', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasIndex('users', self::INDEX)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
