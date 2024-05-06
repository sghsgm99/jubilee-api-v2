<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddErrorFieldsInGoogleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('google_campaigns', function (Blueprint $table) {
            $table->timestamp('errored_at')->nullable();
            $table->text('error_message')->nullable();
        });

        Schema::table('google_adgroups', function (Blueprint $table) {
            $table->timestamp('errored_at')->nullable();
            $table->text('error_message')->nullable();
        });

        Schema::table('google_ads', function (Blueprint $table) {
            $table->timestamp('errored_at')->nullable();
            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('google_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'errored_at',
                'error_message'
            ]);
        });

        Schema::table('google_adgroups', function (Blueprint $table) {
            $table->dropColumn([
                'errored_at',
                'error_message'
            ]);
        });

        Schema::table('google_ads', function (Blueprint $table) {
            $table->dropColumn([
                'errored_at',
                'error_message'
            ]);
        });
    }
}
