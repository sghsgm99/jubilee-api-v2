<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateSiteAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('account_id');
            $table->integer('section');
            $table->string('name');
            $table->string('source')->nullable();
            $table->integer('source_id');
            $table->integer('platform');
            $table->integer('disclosure');
            $table->integer('border');
            $table->integer('organic');
            $table->string('min_slide')->nullable();
            $table->string('max_slide')->nullable();
            $table->text('tags')->nullable();
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
        Schema::dropIfExists('site_ads');
    }
}
