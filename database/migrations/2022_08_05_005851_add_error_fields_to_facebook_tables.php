<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddErrorFieldsToFacebookTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->timestamp('errored_at')->nullable();
            $table->text('error_message')->nullable();
        });

        Schema::table('facebook_adsets', function (Blueprint $table) {
            $table->timestamp('errored_at')->nullable();
            $table->text('error_message')->nullable();
        });

        Schema::table('facebook_ads', function (Blueprint $table) {
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
        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'errored_at',
                'error_message'
            ]);
        });

        Schema::table('facebook_adsets', function (Blueprint $table) {
            $table->dropColumn([
                'errored_at',
                'error_message'
            ]);
        });

        Schema::table('facebook_ads', function (Blueprint $table) {
            $table->dropColumn([
                'errored_at',
                'error_message'
            ]);
        });
    }
}
