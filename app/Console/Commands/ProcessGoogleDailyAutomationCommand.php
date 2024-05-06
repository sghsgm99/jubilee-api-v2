<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleRuleAutomation;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;
use App\Models\Enums\GoogleRuleFrequencyEnum;

class ProcessGoogleDailyAutomationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:process-google-daily-automation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all google daily automations';

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
                    == GoogleRuleFrequencyEnum::DAILY()) {
                    Auth::loginUsingId($ruleAutomation->user_id);

                    Log::info('GoogleRuleAutomation daily process at: ' . now()->toDateTimeString() . ' by: '  . $ruleAutomation->name);
                    $ruleAutomation->Service()->processAutomation();
                }
            }
        }

        return 0;
    }
}
