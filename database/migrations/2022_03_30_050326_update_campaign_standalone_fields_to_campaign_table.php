<?php

use App\Models\Enums\CampaignTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCampaignStandaloneFieldsToCampaignTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->tinyInteger('type')->default(CampaignTypeEnum::REGULAR)->after('status');
            $table->string('primary_text')->nullable()->after('type');
            $table->string('headline')->nullable()->after('primary_text');
            $table->text('ad_description')->nullable()->after('headline');
            $table->string('display_link')->nullable()->after('ad_description');
            $table->string('call_to_action')->nullable()->after('display_link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'primary_text',
                'headline',
                'ad_description',
                'display_link',
                'call_to_action'
            ]);
        });
    }
}
