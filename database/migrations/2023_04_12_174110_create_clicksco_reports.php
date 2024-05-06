<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Schema\SchemaBuilder;

class CreateClickscoReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clicksco_reports', function (Blueprint $table) {
            $table->id();

            SchemaBuilder::BelongsToAccountSchemaUp($table, false);

            $table->string('name');
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
        Schema::dropIfExists('clicksco_reports');
    }
}
