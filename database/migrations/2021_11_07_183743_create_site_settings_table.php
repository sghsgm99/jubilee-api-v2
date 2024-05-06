<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /***
         * id
         * title
         * description — wysywig
         * theme_id 
         * about_us_blurb  — wysywig
         * contact_us_blurb  — wysywig
         * logo @images
         * site_id
         * favicon @images
         */
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            /**
             * Site Themes
             * id
             * title
             * handle
             * status
             */
            $table->integer('theme_id');
            $table->longText('about_us_blurb');
            $table->longText('contact_us_blurb');
            $table->longText('site_id');
            $table->integer('status');
            SchemaBuilder::TimestampSchemaUp($table);
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
            Schema::dropIfExists('site_settings');
        });
    }
}
