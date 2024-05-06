<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMenuIdsToArticleSite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_site', function (Blueprint $table) {
            $table->text('menu_ids')->nullable()->after('tag_ids');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_site', function (Blueprint $table) {
            $table->dropColumn(['menu_ids']);
        });
    }
}
