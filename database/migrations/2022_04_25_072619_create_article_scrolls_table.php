<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleScrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_scrolls', function (Blueprint $table) {
            $table->id();
            $table->integer('article_id');
            $table->integer('order');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('image_description')->nullable();
            SchemaBuilder::BelongsToAccountSchemaUp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_scrolls');
    }
}
