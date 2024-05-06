<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContentColumnsInCollectionGroupCreativesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collection_group_creatives', function (Blueprint $table) {
            $table->text('url')->after('data')->nullable();
            $table->text('call_to_action')->after('data')->nullable();
            $table->text('text')->after('data')->nullable();
            $table->text('headline')->after('data')->nullable();
            $table->text('title')->after('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collection_group_creatives', function (Blueprint $table) {
            $table->dropColumn([
                'url',
                'call_to_action',
                'text',
                'headline',
                'title',
            ]);
        });
    }
}
