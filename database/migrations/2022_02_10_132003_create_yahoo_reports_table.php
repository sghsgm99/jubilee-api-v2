<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYahooReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yahoo_reports', function (Blueprint $table) {
            $table->id();

            SchemaBuilder::BelongsToAccountSchemaUp($table, false);

            $table->integer('type');
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
        Schema::dropIfExists('yahoo_reports');
    }
}
