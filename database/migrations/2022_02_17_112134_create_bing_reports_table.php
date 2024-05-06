<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBingReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bing_reports', function (Blueprint $table) {
            $table->id();

            SchemaBuilder::BelongsToAccountSchemaUp($table, false);

            $table->bigInteger('job_id');
            $table->string('job_id_string');
            $table->string('name');
            $table->string('status');
            $table->longText('download_url');
            $table->json('data')->nullable();
            $table->timestamp('reported_at');

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
        Schema::dropIfExists('bing_reports');
    }
}
