<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password_helper_key')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password_helper_key')
                    ->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'password_helper_key')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_helper_key');
            });
        }
    }
};
