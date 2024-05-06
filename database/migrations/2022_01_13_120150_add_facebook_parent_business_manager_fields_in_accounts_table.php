<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFacebookParentBusinessManagerFieldsInAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->text('facebook_access_token')->after('company_name')->nullable();
            $table->string('facebook_client_id')->after('company_name')->nullable();
            $table->string('facebook_business_manager_id')->after('company_name')->nullable();
            $table->string('facebook_app_secret')->after('company_name')->nullable();
            $table->string('facebook_app_id')->after('company_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_access_token',
                'facebook_client_id',
                'facebook_business_manager_id',
                'facebook_app_secret',
                'facebook_app_id',
            ]);
        });
    }
}
