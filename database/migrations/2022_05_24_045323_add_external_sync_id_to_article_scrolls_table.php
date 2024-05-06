<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExternalSyncIdToArticleScrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_scrolls', function (Blueprint $table) {
            $table->string('title', 1024)->change();

            $table->bigInteger('external_sync_id')->unsigned()->nullable()->after('account_id');
            $table->longText('external_sync_data')->nullable()->after('external_sync_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_scrolls', function (Blueprint $table) {
            $table->dropColumn([
                'external_sync_id',
                'external_sync_data'
            ]);
        });
    }
}
