<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookAdsetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facebook_adsets', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id');
            $table->string('fb_adset_id')->nullable();
            $table->string('title', 255);
            $table->longText('data')->nullable();
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
        Schema::dropIfExists('facebook_adsets');
    }
}
