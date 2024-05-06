<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFielsToCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->text('urls')->nullable()->after('status');
            $table->string('action2')->nullable()->after('status');
            $table->string('action1')->nullable()->after('status');
            $table->string('text2')->nullable()->after('status');
            $table->string('text1')->nullable()->after('status');
            $table->string('headline2')->nullable()->after('status');
            $table->string('headline1')->nullable()->after('status');
            $table->integer('collection_group_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collections', function (Blueprint $table) {
            //
        });
    }
}
