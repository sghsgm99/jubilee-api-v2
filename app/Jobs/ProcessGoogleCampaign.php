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
use App\Models\GoogleCampaign;
use Throwable;

class ProcessGoogleCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $googleCampaign;
    protected $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GoogleCampaign $googleCampaign, int $action)
    {
        $this->googleCampaign = $googleCampaign;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->googleCampaign->user_id);

        $user = User::findOrFail($this->googleCampaign->user_id);

        Log::info('ProcessGoogleCampaign process at: ' . now()->toDateTimeString() . ' by: '  . $user->first_name);

        if ($this->action == 1)
            $this->googleCampaign->Service()->processCampaign();

        if ($this->action == 2)
            $this->googleCampaign->Service()->publishCampaign();
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info('ProcessGoogleCampaign failed: ' .$exception->getMessage());
        
        $this->googleCampaign->setProcessToError($exception->getMessage());
    }
}
