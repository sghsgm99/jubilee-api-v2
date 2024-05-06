<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrderFieldOfArticleTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_quizzes', function (Blueprint $table) {
            $table->integer('order')->unsigned()->nullable()->change();
        });

        Schema::table('article_scrolls', function (Blueprint $table) {
            $table->integer('order')->unsigned()->nullable()->change();
        });

        Schema::table('article_galleries', function (Blueprint $table) {
            $table->integer('order')->unsigned()->nullable()->change();
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
