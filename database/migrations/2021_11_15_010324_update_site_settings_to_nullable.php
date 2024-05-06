<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSiteSettingsToNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // $columns = [
            //     'theme_id',
            //     'about_us_blurb',
            //     'contact_us_blurb',
            //     'site_id',
            //     'status',
            // ];
            // $table->dropColumn($columns);

            // if (!Schema::hasColumn('site_settings', 'theme_id')) {
            //     $table->integer('theme_id')->nullable();
            // }
            // if (!Schema::hasColumn('site_settings', 'about_us_blurb')) {
            //     $table->longText('about_us_blurb')->nullable();
            // }
            // if (!Schema::hasColumn('site_settings', 'contact_us_blurb')) {
            //     $table->longText('contact_us_blurb')->nullable();
            // }
            // if (!Schema::hasColumn('site_settings', 'site_id')) {
            //     $table->longText('site_id')->nullable();
            // }
            // if (!Schema::hasColumn('site_settings', 'status')) {
            //     $table->integer('status')->nullable();
            // }

            $table->integer('theme_id')->nullable()->change();
            $table->longText('about_us_blurb')->nullable()->change();
            $table->longText('contact_us_blurb')->nullable()->change();
            $table->longText('site_id')->nullable()->change();
            $table->integer('status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site_settings', function (Blueprint $table) { });
    }
}
