<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFacebookApiFeildsToChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('api_secret_key')->nullable()->after('api_key');
            $table->string('ad_account_key')->nullable()->after('api_secret_key');
            $table->longText('access_token')->nullable()->after('api_permissions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn([
                'api_secret_key',
                'ad_account_key',
                'access_token',
            ]);
        });
    }
}
