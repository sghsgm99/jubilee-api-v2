<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldInGoogleAdgroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('google_adgroups', function (Blueprint $table) {
            $table->tinyInteger('type')->after('bid')->default(3);
            $table->json('data')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('google_adgroups', function (Blueprint $table) {
            //
        });
    }
}
