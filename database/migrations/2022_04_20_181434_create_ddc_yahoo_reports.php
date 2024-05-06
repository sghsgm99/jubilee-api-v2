<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateDdcYahooReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yahoo_ddc_reports', function (Blueprint $table) {
            $table->id();
            SchemaBuilder::BelongsToAccountSchemaUp($table, false);
            $table->string('type');
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
        Schema::dropIfExists('yahoo_ddc_reports');
    }
}
