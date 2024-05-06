<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelFacebooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_facebook', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('child_business_manager_id');
            $table->string('page_id');
            $table->string('vertical');
            $table->text('page_permitted_tasks');
            $table->integer('timezone');
            $table->unsignedBigInteger('channel_id')->index();
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            SchemaBuilder::TimestampSchemaUp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_facebook');
    }
}
