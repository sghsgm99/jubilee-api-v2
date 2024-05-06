<?php

use App\Models\RoleSetupTemplate;
use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleSetupTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_setup_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id');
            $table->string('setup_name');
            $table->text('setup');
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
        Schema::dropIfExists('role_setup_templates');
    }
}
