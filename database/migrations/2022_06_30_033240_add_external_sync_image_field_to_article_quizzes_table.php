<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExternalSyncImageFieldToArticleQuizzesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_quizzes', function (Blueprint $table) {
            $table->text('external_sync_image')->nullable()->after('external_sync_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_quizzes', function (Blueprint $table) {
            $table->dropColumn(['external_sync_image']);
        });
    }
}
