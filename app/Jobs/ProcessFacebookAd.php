<?php

namespace App\Jobs;

use App\Models\FacebookAd;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProcessFacebookAd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The FacebookCampaign instance.
     *
     * @var FacebookAd
     */
    protected $facebookAd;

    /**
     * Create a new job instance.
     *
     * @param  FacebookAd $facebookAd
     * @return void
     */
    public function __construct(FacebookAd $facebookAd)
    {
        $this->facebookAd = $facebookAd;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->facebookAd->user_id);

        $this->facebookAd->Service()->publishAd();
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $this->facebookAd->setProcessToError($exception->getMessage());

        /*
         * TODO send email or in-app notification to user. But we don't have that yet.
         * So for now just include the error timestamp and message when returning a resource
         */
    }
}
