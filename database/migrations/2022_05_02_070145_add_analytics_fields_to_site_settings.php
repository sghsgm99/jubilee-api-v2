<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnalyticsFieldsToSiteSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->longText('header_tags')->nullable()->after('contact_us_blurb');
            $table->longText('body_tags')->nullable()->after('header_tags');
            $table->longText('footer_tags')->nullable()->after('body_tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'header_tags',
                'body_tags',
                'footer_tags',
            ]);
        });
    }
}
