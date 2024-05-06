<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookRuleDurationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facebook_rule_duration', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('facebook_rule_automation_id')->index();
            $table->foreign('facebook_rule_automation_id')->references('id')->on('facebook_rule_automations')->onDelete('cascade');

            $table->tinyInteger('target');
            $table->tinyInteger('action');
            $table->text('data');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamp('completed_at')->nullable();

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
        Schema::dropIfExists('facebook_rule_duration');
    }
}
