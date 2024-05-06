<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviewLinkFieldToBuilderSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('builder_sites', function (Blueprint $table) {
            $table->string('preview_link', 255)->nullable()->after('path');
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
                'preview_link'
            ]);
        });
    }
}
