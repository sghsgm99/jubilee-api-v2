<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRulesetsScheduleField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rulesets', function (Blueprint $table) {
            $table->dropColumn('schedule_sel');
            $table->dropColumn('frequency');
            $table->dropColumn('onetime_date');
            $table->dropColumn('onetime_start');
            $table->dropColumn('onetime_end');
            $table->dropColumn('daily_start');
            $table->dropColumn('daily_end');
            $table->dropColumn('weekly_start');
            $table->dropColumn('weekly_end');
            $table->dropColumn('duration_start');
            $table->dropColumn('duration_end');
            $table->dropColumn('onetime_24hrs');
            $table->dropColumn('daily_24hrs');
            $table->dropColumn('weekly_24hrs');
            $table->dropColumn('duration_noend');
            $table->dropColumn('weekly_sel');
            $table->longText('schedule')->after('turn_state');
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
