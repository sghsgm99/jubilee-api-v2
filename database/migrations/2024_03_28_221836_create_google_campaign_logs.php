<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleCampaignLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_campaign_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 255);
            $table->string('link_url', 500);
            $table->string('user_agent', 255);
            $table->string('referrer', 255)->nullable();
            $table->string('type', 255)->nullable();
            $table->tinyInteger('position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('google_campaign_logs');
    }
}
