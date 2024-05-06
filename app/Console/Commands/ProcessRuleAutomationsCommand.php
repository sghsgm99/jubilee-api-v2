<?php

namespace App\Console\Commands;

use App\Models\FacebookRuleAutomation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessRuleAutomationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:process-rule-automations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all facebook rule automations';

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
        Log::info('Start FacebookRuleAutomation ' . date('Y-m-d H:i:s'));
        $ruleAutomations = FacebookRuleAutomation::has('facebookCampaigns');

        foreach ($ruleAutomations->cursor() as $ruleAutomation) {
            Log::info('FacebookRuleAutomation process at: ' . now()->toDateTimeString() . ' by: '  . $ruleAutomation->user_id);

            /** @var FacebookRuleAutomation $ruleAutomation */
            Auth::loginUsingId($ruleAutomation->user_id);

            $ruleAutomation->Service()->processAutomation();
        }

        Log::info('End FacebookRuleAutomation ' . date('Y-m-d H:i:s'));
        return 0;
    }
}
