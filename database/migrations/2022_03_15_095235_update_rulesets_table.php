<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRulesetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rulesets', function (Blueprint $table) {
            $table->dropColumn('traffic');
            $table->dropColumn('turn');
            $table->float('traffic_per')->after('advertiser');
            $table->boolean('turn_state')->after('traffic');
            $table->tinyInteger('schedule_sel')->after('turn');
            $table->tinyInteger('frequency')->after('schedule_sel');
            $table->string('onetime_date', 255)->nullable();
            $table->string('onetime_start', 255)->nullable();
            $table->string('onetime_end', 255)->nullable();
            $table->string('daily_start', 255)->nullable();
            $table->string('daily_end', 255)->nullable();
            $table->string('weekly_start', 255)->nullable();
            $table->string('weekly_end', 255)->nullable();
            $table->string('duration_start', 255)->nullable();
            $table->string('duration_end', 255)->nullable();
            $table->boolean('onetime_24hrs');
            $table->boolean('daily_24hrs');
            $table->boolean('weekly_24hrs');
            $table->boolean('duration_noend');
            $table->string('weekly_sel', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
