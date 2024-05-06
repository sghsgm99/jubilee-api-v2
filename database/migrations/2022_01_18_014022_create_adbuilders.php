<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateAdbuilders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adbuilders', function (Blueprint $table) {
            $table->id();
            $table->string('name',255)->unique();
            $table->string('url',255);
            $table->longText('gjs_components')->nullable();
            $table->longText('gjs_style')->nullable();
            $table->longText('gjs_html')->nullable();
            $table->longText('gjs_css')->nullable();
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
        Schema::dropIfExists('adbuilders');
    }
}
