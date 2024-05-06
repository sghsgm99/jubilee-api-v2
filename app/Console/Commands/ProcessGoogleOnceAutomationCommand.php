<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleRuleAutomation;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;
use App\Models\Enums\GoogleRuleFrequencyEnum;
use DateTime;

class ProcessGoogleOnceAutomationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:process-google-once-automation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all google once automations';

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

        $currentDateTime = new DateTime();

        //Log::info('Start Once GoogleRuleAutomation ' . date('Y-m-d H:i:s'));

        foreach ($ruleAutomations->cursor() as $ruleAutomation) {
            if ($ruleAutomation->status == CampaignStatus::ENABLED) {
                if (GoogleRuleFrequencyEnum::memberByValue($ruleAutomation->frequency)
                    == GoogleRuleFrequencyEnum::ONCE()) {
                    $currentValue = $currentDateTime->format('Y-m-d H:i');

                    if ($currentValue == $ruleAutomation->updated_at->modify('+1 minutes')->format('Y-m-d H:i')) {
                        Auth::loginUsingId($ruleAutomation->user_id);

                        Log::info('GoogleRuleAutomation once process at: ' . now()->toDateTimeString() . ' by: '  . $ruleAutomation->name);
                        $ruleAutomation->Service()->processAutomation();
                    }                    
                }
            }
        }

        //Log::info('End Once GoogleRuleAutomation ' . date('Y-m-d H:i:s'));

        return 0;
    }
}
