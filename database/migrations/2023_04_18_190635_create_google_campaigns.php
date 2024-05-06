<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateGoogleCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('gg_campaign_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->bigInteger('customer_id');
            $table->float('budget');
            $table->tinyInteger('status')->default(3);
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
        Schema::dropIfExists('google_campaigns');
    }
}
