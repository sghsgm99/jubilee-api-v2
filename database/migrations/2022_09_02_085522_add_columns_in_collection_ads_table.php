<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInCollectionAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collection_ads', function (Blueprint $table) {
            $table->longText('add_url')->after('group_id')->nullable();
            $table->longText('add_call_to_action')->after('group_id')->nullable();
            $table->longText('add_text')->after('group_id')->nullable();
            $table->longText('add_headline')->after('group_id')->nullable();
            $table->longText('add_title')->after('group_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collection_ads', function (Blueprint $table) {
            $table->dropColumn([
                'add_url',
                'add_call_to_action',
                'add_text',
                'add_headline',
                'add_title',
            ]);
        });
    }
}
