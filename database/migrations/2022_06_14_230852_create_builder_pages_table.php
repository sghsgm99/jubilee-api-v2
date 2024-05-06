<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuilderPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('builder_pages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('builder_site_id')->index();
            $table->foreign('builder_site_id')->references('id')->on('builder_sites')->onDelete('cascade');

            $table->string('title');
            $table->string('slug', 510);
            $table->longText('html')->nullable();
            $table->longText('styling')->nullable();
            $table->text('seo')->nullable();
            $table->integer('order')->default(0);

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
        Schema::dropIfExists('builder_pages');
    }
}
