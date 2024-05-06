<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookAutomationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facebook_automation_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('facebook_campaign_id')->index();
            $table->foreign('facebook_campaign_id')->references('id')->on('facebook_campaigns')->cascadeOnDelete();

            $table->unsignedBigInteger('facebook_rule_automation_id')->index();
            $table->foreign('facebook_rule_automation_id')->references('id')->on('facebook_rule_automations')->cascadeOnDelete();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('errored_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('error_message', 255)->nullable();

            SchemaBuilder::BelongsToAccountSchemaUp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facebook_automation_logs');
    }
}
