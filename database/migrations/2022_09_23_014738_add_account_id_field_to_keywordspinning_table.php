<?php

use App\Models\Schema\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountIdFieldToKeywordspinningTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('keywordspinning', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->index()->after('category')->default(1);
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('keywordspinning', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn(['account_id']);
        });
    }
}
