<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeployerFiedsToBuilderSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('builder_sites', function (Blueprint $table) {
            $table->string('api_builder_key')->nullable()->after('seo');
            $table->string('host')->nullable()->after('api_builder_key');
            $table->string('ssh_username')->nullable()->after('host');
            $table->string('ssh_password')->nullable()->after('ssh_username');
            $table->string('path')->nullable()->after('ssh_password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('builder_sites', function (Blueprint $table) {
            $table->dropColumn([
                'api_builder_key',
                'host',
                'ssh_username',
                'ssh_password',
                'path',
            ]);
        });
    }
}
