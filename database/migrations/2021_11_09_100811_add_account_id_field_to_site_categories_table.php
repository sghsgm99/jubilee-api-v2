<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountIdFieldToSiteCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->index()->after('label');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site_categories', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn(['account_id']);
        });
    }
}
