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
use App\Models\GoogleAdgroup;
use Throwable;

class ProcessGoogleAdgroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $googleAdgroup;
    protected $action;
    protected $value;
    protected $ggIdsArray;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GoogleAdgroup $googleAdgroup, int $action, int $value = null, array $ggIdsArray = [])
    {
        $this->googleAdgroup = $googleAdgroup;
        $this->action = $action;
        $this->value = $value;
        $this->ggIdsArray = $ggIdsArray;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Auth::loginUsingId($this->googleAdgroup->user_id);

        if ($this->action == 1)
            $this->googleAdgroup->Service()->processAdgroup();

        if ($this->action == 2)
            $this->googleAdgroup->Service()->publishAdgroup();

        if ($this->action == 3)
            $this->googleAdgroup->Service()->publishAdgroupAd($this->value);

        if ($this->action == 4)
            $this->googleAdgroup->Service()->publishAdgroupAdEx($this->value, $this->ggIdsArray);
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
            "ProcessGoogleAdgroup failed:%s%s",
            $exception->getMessage(),
            PHP_EOL
        ));
        
        $this->googleAdgroup->setProcessToError($exception->getMessage());
    }
}
