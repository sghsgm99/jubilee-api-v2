<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedCategoryTagIdToArticleSiteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_site', function (Blueprint $table) {
            $table->text('category_ids')->nullable()->after('external_post_id');
            $table->text('tag_ids')->nullable()->after('category_ids');
            $table->string('status')->after('tag_ids');

            $table->unsignedBigInteger('external_post_id')->nullable()->change();

            $table->dropColumn(['type']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['tags']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_site', function (Blueprint $table) {
            $table->dropColumn(['category_ids', 'tag_ids', 'status']);
        });
    }
}
