<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserAccessTokenInChannelFacebookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_facebook', function (Blueprint $table) {
            $table->string('user_access_token')->nullable()->before('access_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_facebook', function (Blueprint $table) {
            $table->dropColumn(['user_access_token']);
        });
    }
}
