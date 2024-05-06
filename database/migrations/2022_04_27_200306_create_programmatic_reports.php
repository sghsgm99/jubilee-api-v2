<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateProgrammaticReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programmatic_reports', function (Blueprint $table) {
            $table->id();
            SchemaBuilder::BelongsToAccountSchemaUp($table, false);
            $table->json('data')->nullable();
            $table->timestamp('reported_at')->nullable();
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
        Schema::dropIfExists('programmatic_reports');
    }
}
