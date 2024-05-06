<?php

namespace App\Traits;

use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;

trait GoogleQueueableTrait
{
    public function setProcessToComplete(): void
    {
        $this->errored_at = null;
        $this->error_message = null;
        $this->save();
    }

    /**
     * @param string $message
     * @return void
     */
    public function setProcessToError(string $message, int $status = null): void
    {
        if ($status === null) {
            $status = CampaignStatus::UNKNOWN;
        }

        $this->status = $status;
        $this->errored_at = now();
        $this->error_message = $message;
        $this->save();
    }
}
