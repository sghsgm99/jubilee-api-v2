<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFacebookAutomationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('facebook_automation_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facebook_campaign_id');

            $table->integer('loggable_id')->after('facebook_rule_automation_id');
            $table->string('loggable_type')->after('loggable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facebook_automation_logs', function (Blueprint $table) {
            $table->dropColumn([
                'loggable_id',
                'loggable_type'
            ]);
        });
    }
}
