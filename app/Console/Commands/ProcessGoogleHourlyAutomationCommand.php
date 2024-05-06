<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleRuleAutomation;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;
use App\Models\Enums\GoogleRuleFrequencyEnum;

class ProcessGoogleHourlyAutomationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:process-google-hourly-automation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all google hourly automations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ruleAutomations = GoogleRuleAutomation::query();

        foreach ($ruleAutomations->cursor() as $ruleAutomation) {
            if ($ruleAutomation->status == CampaignStatus::ENABLED) {              
                if (GoogleRuleFrequencyEnum::memberByValue($ruleAutomation->frequency) 
                    == GoogleRuleFrequencyEnum::HOURLY()) {
                    Auth::loginUsingId($ruleAutomation->user_id);

                    Log::info('GoogleRuleAutomation hourly process at: ' . now()->toDateTimeString() . ' by: '  . $ruleAutomation->name);
                    $ruleAutomation->Service()->processAutomation();
                }
            }
        }

        return 0;
    }
}
