<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateGoogleReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_reports', function (Blueprint $table) {
            $table->id();
            SchemaBuilder::BelongsToAccountSchemaUp($table, false);
            $table->string('name');
            $table->json('data')->nullable();
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
        Schema::dropIfExists('google_reports');
    }
}
