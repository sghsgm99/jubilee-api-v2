<?php

namespace App\Jobs;

use App\Models\FacebookCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Throwable;

class ProcessFacebookCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The FacebookCampaign instance.
     *
     * @var FacebookCampaign
     */
    protected $facebookCampaign;

    /**
     * Create a new job instance.
     *
     * @param  FacebookCampaign $facebookCampaign
     * @return void
     */
    public function __construct(FacebookCampaign $facebookCampaign)
    {
        $this->facebookCampaign = $facebookCampaign;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->facebookCampaign->user_id);

        $user = User::findOrFail($this->facebookCampaign->user_id);

        Log::info('ProcessFacebookCampaign process at: ' . now()->toDateTimeString() . ' by: '  . $user->first_name);

        $this->facebookCampaign->Service()->publishCampaign();
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $this->facebookCampaign->setProcessToError($exception->getMessage());

        /*
         * TODO send email or in-app notification to user. But we don't have that yet.
         * So for now just include the error timestamp and message when returning a resource
         */
    }
}
