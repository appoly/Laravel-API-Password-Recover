<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPasswordHelperKeyToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasColumn('password_helper_key')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password_helper_key')
                    ->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('password_helper_key')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_helper_key');
            });
        }
    }
}
