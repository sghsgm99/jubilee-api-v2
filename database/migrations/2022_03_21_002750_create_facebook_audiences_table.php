<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookAudiencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facebook_audiences', function (Blueprint $table) {
            $table->id();
            $table->string('audience_name');
            $table->text('audience_description')->nullable();
            $table->integer('audience_id');
            $table->integer('channel_id');
            $table->string('audience_type');
            $table->longText('setup_details');
            SchemaBuilder::BelongsToAccountSchemaUp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facebook_audiences');
    }
}
