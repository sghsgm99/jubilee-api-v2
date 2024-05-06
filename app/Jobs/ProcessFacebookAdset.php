<?php

namespace App\Jobs;

use App\Models\FacebookAdset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProcessFacebookAdset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The FacebookCampaign instance.
     *
     * @var FacebookAdset
     */
    protected $facebookAdset;

    /**
     * Create a new job instance.
     *
     * @param  FacebookAdset $facebookAdset
     * @return void
     */
    public function __construct(FacebookAdset $facebookAdset)
    {
        $this->facebookAdset = $facebookAdset;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->facebookAdset->user_id);

        $this->facebookAdset->Service()->publishAdset();
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $this->facebookAdset->setProcessToError($exception->getMessage());

        /*
         * TODO send email or in-app notification to user. But we don't have that yet.
         * So for now just include the error timestamp and message when returning a resource
         */
    }
}
