<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema\SchemaBuilder;

class CreateSiteShopifyProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_shopify_products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('site_id');
            $table->longText('data')->nullable();
            SchemaBuilder::BelongsToUserSchemaUp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_shopify_products');
    }
}
