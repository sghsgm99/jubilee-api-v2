<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToKeywordspinnning extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('keywordspinning', function (Blueprint $table) {
            $table->integer('clicks')->after('url')->default(0);
            $table->integer('impr')->after('url')->default(0);
            $table->integer('ctr')->after('url')->default(0);
            $table->float('cpc')->after('url')->default(0);
            $table->integer('conversion')->after('url')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('keywordspinning', function (Blueprint $table) {
            $table->dropColumn([
                'clicks',
                'impr',
                'ctr',
                'cpc',
                'conversion'
            ]);
        });
    }
}
