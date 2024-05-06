<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\GoogleRuleAutomation;
use Throwable;

class ProcessGoogleAutomation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $googleRuleAutomation;
    protected $ggIdsArray;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GoogleRuleAutomation $googleRuleAutomation, array $ggIdsArray = [])
    {
        $this->googleRuleAutomation = $googleRuleAutomation;
        $this->ggIdsArray = $ggIdsArray;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->googleRuleAutomation->user_id);

        $user = User::findOrFail($this->googleRuleAutomation->user_id);

        Log::info('ProcessGoogleAutomation process at: ' . now()->toDateTimeString() . ' by: '  . $user->first_name);

        $this->googleRuleAutomation->Service()->processGoogleAutomation($this->ggIdsArray);
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info('ProcessGoogleAutomation failed: ' .$exception->getMessage());
        
        $this->googleRuleAutomation->setProcessToError($exception->getMessage());
    }
}
