<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateGoogleAiCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_ai_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('base_url', 255);
            $table->float('budget');
            $table->float('bid');
            $table->bigInteger('customer_id');
            $table->string('final_url', 255);
            $table->tinyInteger('status')->default(0);
            $table->string('campaign_id')->nullable();
            $table->string('adgroup_id')->nullable();
            $table->string('ad_id')->nullable();
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
        Schema::dropIfExists('google_ai_campaigns');
    }
}
