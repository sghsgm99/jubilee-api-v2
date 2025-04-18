<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodIdInChannelFacebookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_facebook', function (Blueprint $table) {
            $table->string('payment_method_id')->nullable()->after('page_id');
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
            $table->dropColumn(['payment_method_id']);
        });
    }
}
