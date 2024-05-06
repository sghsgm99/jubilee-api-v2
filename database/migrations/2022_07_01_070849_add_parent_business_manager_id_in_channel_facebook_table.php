<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentBusinessManagerIdInChannelFacebookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_facebook', function (Blueprint $table) {
            $table->string('parent_business_manager_id')->after('channel_id')->nullable();
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
            $table->dropColumn(['parent_business_manager_id']);
        });
    }
}
