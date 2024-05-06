<?php

namespace App\Models\Services;

use App\Jobs\ProcessFacebookCampaign;
use App\Models\Channel;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\FacebookCampaign;
use App\Models\FacebookRuleAutomation;
use App\Services\FacebookCampaignService;
use App\Traits\ImageModelServiceTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacebookCampaignModelService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var Channel
     */
    private $facebookCampaign;

    public function __construct(FacebookCampaign $facebookCampaign)
    {
        $this->facebookCampaign = $facebookCampaign;
        $this->model = $facebookCampaign; // required
    }

    public static function create(
        Channel $channel,
        string $ad_account_id,
        string $title,
        string $description,
        FacebookCampaignObjectiveEnum $objective,
        CampaignInAppStatusEnum $status
    )
    {
        try {
            DB::beginTransaction();

            $facebookCampaign = new FacebookCampaign();
            $facebookCampaign->channel_id = $channel->id;
            $facebookCampaign->ad_account_id = $ad_account_id;
            $facebookCampaign->title = $title;
            $facebookCampaign->description = $description;
            $facebookCampaign->objective = $objective;
            $facebookCampaign->status = $status;
            $facebookCampaign->user_id = Auth::user()->id;
            $facebookCampaign->account_id = Auth::user()->account_id;
            $facebookCampaign->fb_status = FacebookCampaignStatusEnum::PAUSED;
            $facebookCampaign->save();

            DB::commit();

            if ($facebookCampaign->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookCampaign::dispatch($facebookCampaign);
            }

            return $facebookCampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['error' => $th->getErrorUserMessage() ?? $th->getMessage()];
        }
    }

    public function update(
        string $title,
        string $description,
        CampaignInAppStatusEnum $status
    )
    {
        try {
            DB::beginTransaction();

            $this->facebookCampaign->title = $title;
            $this->facebookCampaign->description = $description;
            $this->facebookCampaign->status = $status->value;
            $this->facebookCampaign->save();

            DB::commit();
            $facebookCampaign = $this->facebookCampaign->fresh();

            if ($this->facebookCampaign->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookCampaign::dispatch($facebookCampaign);
            }

            return $facebookCampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['error' => $th->getErrorUserMessage() ?? $th->getMessage()];
        }
    }

    /**
     * @throws \Exception
     */
    public function publishCampaign()
    {
        if ($this->facebookCampaign->status->is(CampaignInAppStatusEnum::DRAFT())) {
            return [];
        }

        if (! $this->facebookCampaign->channel) {
            throw new \Exception('This campagin do not have a channel');
        }

        Log::info('publishCampaign process at: ' . now()->toDateTimeString() . ' by: '  . $this->facebookCampaign->fb_campaign_id);

        $fbCampaignService = FacebookCampaignService::resolve($this->facebookCampaign->channel);

        // update campaign coz it's already publish
        if ($this->facebookCampaign->fb_campaign_id) {
            try {
                $fbCampaignService->updateCampaign(
                    $this->facebookCampaign->fb_campaign_id,
                    $this->facebookCampaign->title,
                    null,
                    $this->facebookCampaign->fb_status->value ?? null
                );
            } catch (\Throwable $th) {
                throw new \Exception($th->getErrorUserMessage() ?? $th->getMessage());
            }
        }

        // publish campaign to facebook
        if (! $this->facebookCampaign->fb_campaign_id) {
            try {
                $fbCampaign = $fbCampaignService->createCampaign(
                    $this->facebookCampaign->title,
                    $this->facebookCampaign->objective->value,
                    [],
                    $this->facebookCampaign->ad_account_id
                );
            } catch (\Throwable $th) {
                throw new \Exception($th->getErrorUserMessage() ?? $th->getMessage());
            }
        }

        $this->facebookCampaign->fb_campaign_id = $fbCampaign['id'] ?? $this->facebookCampaign->fb_campaign_id;
        $this->facebookCampaign->save();

        $this->facebookCampaign->setProcessToComplete();
        return $this->facebookCampaign->fresh();
    }

    public function duplicate(bool $deep = false)
    {
        try {
            DB::beginTransaction();

            if($this->facebookCampaign->fb_campaign_id || $this->facebookCampaign->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                $campaign = FacebookCampaignService::resolve($this->facebookCampaign->channel)->duplicateCampaign($this->facebookCampaign->fb_campaign_id, true);

                if(isset($campaign['error'])) {
                    return $campaign;
                }
            }

            $duplicateCampaign = $this->facebookCampaign->replicate()->fill([
                'fb_campaign_id' => $campaign['id'] ?? $this->facebookCampaign->fb_campaign_id,
                'title' => $campaign['name'] ?? $this->facebookCampaign->title.' - Copy'
            ]);
            $duplicateCampaign->save();

            if($deep) {
                foreach ($this->facebookCampaign->adsets as $adset) {
                    $adset->Service()->duplicate(
                        $duplicateCampaign,
                        true
                    );
                }
            }

            DB::commit();

            return $duplicateCampaign;

        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }

    }

    public function toggleStatus()
    {
        if(!$this->facebookCampaign->fb_campaign_id) {
            return [
                'error' => true,
                'message' => 'Cannot toggle on/off when Facebook Campaign is not yet published'
            ];
        }

        try {
            DB::beginTransaction();

            $this->facebookCampaign->fb_status = $this->facebookCampaign->fb_status->isNot(FacebookCampaignStatusEnum::PAUSED())
                ? FacebookCampaignStatusEnum::PAUSED()
                : FacebookCampaignStatusEnum::ACTIVE();
            $this->facebookCampaign->save();

            DB::commit();
            $facebookCampaign = $this->facebookCampaign->fresh();

            if ($this->facebookCampaign->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookCampaign::dispatch($facebookCampaign);
            }

            return $facebookCampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public function attachRuleAutomation(?FacebookRuleAutomation $facebookRuleAutomation)
    {
        $this->facebookCampaign->facebook_rule_automation_id = $facebookRuleAutomation->id ?? null;
        $this->facebookCampaign->save();

        return $this->facebookCampaign->fresh();
    }

    public function delete(): bool
    {
        if ($this->facebookCampaign->adsets->count() > 0) {
            abort('403', 'Cannot delete Campaign if adsets are still available');
        }

        if($this->facebookCampaign->fb_campaign_id) {
            FacebookCampaignService::resolve($this->facebookCampaign->channel)->deleteCampaign($this->facebookCampaign->fb_campaign_id);
        }

        return parent::delete();
    }
}
