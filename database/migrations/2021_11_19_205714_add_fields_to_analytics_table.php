<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAnalyticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('analytics', function (Blueprint $table) {
            $table->integer('impressions')->after('account_id')->unsigned();
            $table->integer('clicks')->after('account_id')->unsigned();
            $table->float('spend', 11,2)->after('account_id')->unsigned();
            $table->integer('channel_id')->after('account_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('analytics', function (Blueprint $table) {
            $table->dropColumn([
                'impressions',
                'clicks',
                'spend',
                'channel_id'
            ]);
        });
    }
}
