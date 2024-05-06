<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleRuleAutomations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_rule_automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('apply_to');
            $table->tinyInteger('apply_to_id');
            $table->tinyInteger('action');
            $table->unsignedInteger('frequency');
            SchemaBuilder::BelongsToUserSchemaUp($table);
        });

        Schema::create('google_rule_automation_conditions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('google_rule_automation_id');
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
        Schema::dropIfExists('google_rule_automations');

        Schema::dropIfExists('google_rule_automation_conditions');
    }
}
