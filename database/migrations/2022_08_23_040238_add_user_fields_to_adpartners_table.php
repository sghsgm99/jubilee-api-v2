<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserFieldsToAdpartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adpartner', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->index()->after('config');
            $table->unsignedBigInteger('account_id')->index()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adpartner', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'account_id'
            ]);
        });
    }
}
