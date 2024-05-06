<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrimaryKeyChecker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('password_resets', 'id')) {
            Schema::table('password_resets', function (Blueprint $table) {
                $table->id()->first();
            });
        }
        
        if (!Schema::hasColumn('campaign_tag_campaign', 'id')) {
            Schema::table('campaign_tag_campaign', function (Blueprint $table) {
                $table->id()->first();
            });
        }

        if (!Schema::hasColumn('campaign_tag_facebook_campaign', 'id')) {
            Schema::table('campaign_tag_facebook_campaign', function (Blueprint $table) {
                $table->id()->first();
            });
        }

        if (!Schema::hasColumn('collection_facebook_ad_account', 'id')) {
            Schema::table('collection_facebook_ad_account', function (Blueprint $table) {
                $table->id()->first();
            });
        }

        if (!Schema::hasColumn('collection_facebook_ads', 'id')) {
            Schema::table('collection_facebook_ads', function (Blueprint $table) {
                $table->id()->first();
            });
        }
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
