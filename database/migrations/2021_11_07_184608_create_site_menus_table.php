<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * id
         * parent_id
         * title
         * slug
         * description
         * order_id
         */
        Schema::create('site_menus', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('order_id');
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
        Schema::table('site_menus', function (Blueprint $table) {
            Schema::dropIfExists('site_menus');
        });
    }
}
