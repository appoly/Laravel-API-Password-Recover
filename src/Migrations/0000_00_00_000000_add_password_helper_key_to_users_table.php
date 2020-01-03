<?php

use Appoly\SmartSchema\SchemaHelper;
use Appoly\SmartSchema\SmartSchema;
use Illuminate\Database\Migrations\Migration;

class AddPasswordHelperKeyToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SmartSchema::table('users', function (SchemaHelper $table) {
            $table->string('password_helper_key')
                ->nullable()
                ->fillable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        SmartSchema::table('users', function (SchemaHelper $table) {
            $table->dropColumn('password_helper_key');
        });
    }
}
