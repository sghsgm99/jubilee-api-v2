<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeInChannelFacebookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_facebook', function (Blueprint $table) {
            $table->integer('type')->after('name')->default(1);
            $table->string('child_business_manager_id')->nullable()->change();
            $table->string('vertical')->nullable()->change();
            $table->text('page_permitted_tasks')->nullable()->change();
            $table->integer('timezone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_facebook', function (Blueprint $table) {
            $table->dropColumn(['type']);
        });
    }
}
