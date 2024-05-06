<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsTopOrBottomToSiteMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_menus', function (Blueprint $table) {
            $table->boolean('is_top')->after('account_id')->default(false);
            $table->boolean('is_bottom')->after('is_top')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site_menus', function (Blueprint $table) {
            $table->dropColumn(['is_top', 'is_bottom']);
        });
    }
}
