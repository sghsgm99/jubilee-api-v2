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
use App\Models\GoogleAICampaign;
use Throwable;

class ProcessGoogleAICampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $googleAICampaign;
    protected $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GoogleAICampaign $googleAICampaign, int $action)
    {
        $this->googleAICampaign = $googleAICampaign;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->googleAICampaign->user_id);

        $user = User::findOrFail($this->googleAICampaign->user_id);

        Log::info('ProcessGoogleAICampaign process at: ' . now()->toDateTimeString() . ' by: '  . $user->first_name);

        if ($this->action == 1)
            $this->googleAICampaign->Service()->processCampaign();

        if ($this->action == 2)
            $this->googleAICampaign->Service()->publishCampaign();
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info('ProcessGoogleAICampaign failed: ' .$exception->getMessage());
        
        $this->googleAICampaign->setProcessToError($exception->getMessage());
    }
}
