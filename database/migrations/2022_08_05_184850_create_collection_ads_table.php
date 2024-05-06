<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateCollectionAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_ads', function (Blueprint $table) {
            $table->id();
            $table->integer('collection_id');
            $table->integer('channel_id');
            $table->string('ad_account_id');
            $table->integer('campaign_id');
            $table->integer('adset_id');
            $table->integer('ads_number');
            $table->integer('group_id');
            $table->integer('status');
            SchemaBuilder::BelongsToUserSchemaUp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collection_ads');
    }
}
