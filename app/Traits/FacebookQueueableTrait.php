<?php

namespace App\Traits;

use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\FacebookAutomationLog;

trait FacebookQueueableTrait
{
    public function setProcessToComplete(): void
    {
        $this->errored_at = null;
        $this->error_message = null;
        $this->save();

        /** @var FacebookAutomationLog $fbAutomationLog */
        $fbAutomationLog = FacebookAutomationLog::query()
            ->where('loggable_id', $this->id)
            ->where('loggable_type', $this->class_name)
            ->whereNull(['errored_at', 'completed_at'])
            ->first();

        if ($fbAutomationLog) {
            $fbAutomationLog->Service()->setCompletedAt();
        }
    }

    /**
     * @param string $message
     * @return void
     */
    public function setProcessToError(string $message, CampaignInAppStatusEnum $statusEnum = null): void
    {
        if ($statusEnum === null) {
            $statusEnum = CampaignInAppStatusEnum::DRAFT();
        }

        $this->status = $statusEnum;
        $this->errored_at = now();
        $this->error_message = $message;
        $this->save();

        /** @var FacebookAutomationLog $fbAutomationLog */
        $fbAutomationLog = FacebookAutomationLog::query()
            ->where('loggable_id', $this->id)
            ->where('loggable_type', $this->class_name)
            ->whereNull(['errored_at', 'completed_at'])
            ->first();

        if ($fbAutomationLog) {
            $fbAutomationLog->Service()->setErroredAt($message);
        }
    }
}
