<?php

namespace App\Models\Services;

use App\Models\CCollection;
use App\Models\User;
use App\Models\Enums\CollectionStatusEnum;
use Illuminate\Support\Facades\DB;

class CollectionService extends ModelService
{
    private $collection;

    public function __construct(CCollection $collection)
    {
        $this->collection = $collection;
        $this->model = $collection; // required
    }

    public static function create(
        ?User $user,
        CollectionStatusEnum $status,
        string $name
    ) {
        $collection = new CCollection();

        $collection->user_id = $user->id ?? null;
        $collection->account_id = auth()->user()->account_id;
        $collection->name = $name;
        $collection->status = $status;
        $collection->save();

        return $collection;
    }

    public function update(
        int $collection_group_id,
        string $headline1 = null,
        string $headline2 = null,
        string $text1 = null,
        string $text2 = null,
        string $action1 = null,
        string $action2 = null,
        array $urls = null
    )
    {
        $this->collection->collection_group_id = $collection_group_id;
        $this->collection->headline1 = $headline1;
        $this->collection->headline2 = $headline2;
        $this->collection->text1 = $text1;
        $this->collection->text2 = $text2;
        $this->collection->action1 = $action1;
        $this->collection->action2 = $action2;
        $this->collection->urls = $urls;
        $this->collection->save();

        return $this->collection->fresh();
    }

    public function duplicate($deep = false)
    {
        try {
            DB::beginTransaction();

            $collection = $this->create(
                auth()->user(),
                CollectionStatusEnum::DRAFT(),
                "{$this->collection->name} - copy"
            );
            
            if($deep) {
                // loop collection group
                foreach ($this->collection->groups as $group) {
                    // duplicate collection group
                    $new_group = CollectionGroupService::duplicate(
                        $group->name,
                        $collection->id,
                        $group->groupCreatives->pluck('creative_id')->toArray()
                    );

                    // loop collection ads
                    foreach ($group->collectionAds as $ads) {
                        // duplicate collection ads
                        CollectionAdService::duplicate(
                            $collection->id,
                            $ads->channel_id,
                            $ads->ad_account_id,
                            $ads->campaign_id,
                            $ads->adset_id,
                            $new_group->id,
                            $ads->ads_number,
                            $ads->add_images,
                            $ads->add_title,
                            $ads->add_headline,
                            $ads->add_text,
                            $ads->add_call_to_action,
                            $ads->add_url
                        );
                    }

                }
            }
            DB::commit();

            return $collection;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }

        

    }

    public function syncFacebookAdAccount(array $fb_account_ids = [])
    {
        if (empty($fb_account_ids)) {
            return $this->collection->facebook_accounts()->detach();
        }

        return $this->collection->facebook_accounts()->sync($fb_account_ids);
    }
}
