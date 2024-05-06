<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facebook_ads', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('adset_id');
            $table->string('fb_ad_id')->nullable();
            $table->bigInteger('article_id')->unsigned()->nullable();
            $table->bigInteger('site_id');
            $table->string('title', 255)->nullable();
            $table->string('primary_text', 255)->nullable();
            $table->string('headline', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('display_link', 255)->nullable();
            $table->string('call_to_action')->nullable();
            $table->tinyInteger('status');
            $table->string('fb_status');
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
        Schema::dropIfExists('facebook_ads');
    }
}
