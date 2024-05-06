<?php

namespace App\Console\Commands;

use App\Models\FacebookRuleDuration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessRuleDurationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:process-rule-duration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all facebook rule automation that needs to end';

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
        Log::info('Start FacebookRuleDuration ' . date('Y-m-d H:i:s'));
        $fbRuleDurations = FacebookRuleDuration::query()
            ->whereNull('completed_at')
            ->where('end_at', '<=', now()->toDateTimeString());

        foreach ($fbRuleDurations->cursor() as $fbRuleDuration) {
            /** @var FacebookRuleDuration $fbRuleDuration */
            Auth::loginUsingId($fbRuleDuration->user_id);

            $fbRuleDuration->Service()->processRuleDuration();
        }

        Log::info('End FacebookRuleDuration ' . date('Y-m-d H:i:s'));
        return 0;
    }
}
