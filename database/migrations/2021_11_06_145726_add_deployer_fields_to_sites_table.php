<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeployerFieldsToSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Host (Address of the client's server, it can be a domain or an IP address)
         * Username (SSH username of the client's server)
         * Password (SSH password of the client's server)
         * Path (Path to the hosted static files in client's server)
         * API keys (to fetch data from Jubilee API during build)
         */
        Schema::table('sites', function (Blueprint $table) {
            $table->string('api_jubilee_key')->nullable()->after('api_key');
            $table->string('host')->nullable()->after('description');
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
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'api_jubilee_key',
                'host',
                'ssh_username',
                'ssh_password',
                'path',
            ]);
        });
    }
}
