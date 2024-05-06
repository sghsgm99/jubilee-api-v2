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
use App\Models\GoogleAd;
use Throwable;

class ProcessGoogleAd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $googleAd;
    protected $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GoogleAd $googleAd, int $action)
    {
        $this->googleAd = $googleAd;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->googleAd->user_id);

        if ($this->action == 1)
            $this->googleAd->Service()->processAd();

        if ($this->action == 2)
            $this->googleAd->Service()->publishAd();
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::info(sprintf(
            "ProcessGoogleAd failed:%s%s",
            $exception->getMessage(),
            PHP_EOL
        ));
        
        $this->googleAd->setProcessToError($exception->getMessage());
    }
}
