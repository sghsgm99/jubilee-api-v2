<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTableOrderIdToOrderTableSiteMenus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_menus', function (Blueprint $table) {
            if (Schema::hasColumn('site_menus', 'order_id')) {
                $table->dropColumn(['order_id']);
            }
            if (!Schema::hasColumn('site_menus', 'sort')) {
                $table->integer('sort')->after('is_bottom')->nullable();
            }
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
            $table->dropColumn(['sort']);
        });
    }
}
