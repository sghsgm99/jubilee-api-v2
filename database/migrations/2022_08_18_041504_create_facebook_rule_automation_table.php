<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookRuleAutomationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('facebook_rule_automation_id')->nullable()->after('fb_status');
        });

        Schema::create('facebook_rule_automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('target');
            $table->tinyInteger('action');
            $table->unsignedInteger('minutes')->nullable();
            SchemaBuilder::BelongsToUserSchemaUp($table);
        });

        Schema::create('facebook_rule_automation_conditions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('facebook_rule_automation_id');
            $table->unsignedTinyInteger('logical_operator')->nullable();
            $table->tinyInteger('target');
            $table->text('conditions');
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
        Schema::dropIfExists('rule_automation');

        Schema::dropIfExists('facebook_rule_automation_conditions');

        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_rule_automation_id',
            ]);
        });
    }
}
