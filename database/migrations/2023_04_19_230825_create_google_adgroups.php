<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateGoogleAdgroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_adgroups', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id');
            $table->string('gg_adgroup_id')->nullable();
            $table->string('title', 255);
            $table->float('bid');
            $table->tinyInteger('status')->default(3);
            SchemaBuilder::BelongsToUserSchemaUp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('google_adgroups');
    }
}
