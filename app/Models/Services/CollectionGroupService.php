<?php

namespace App\Models\Services;

use App\Models\CollectionCreative;
use App\Models\CollectionGroup;
use App\Models\CollectionGroupCreative;
use App\Models\User;

class CollectionGroupService extends ModelService
{
    private $group;

    public function __construct(CollectionGroup $group)
    {
        $this->group = $group;
        $this->model = $group; // required
    }

    public static function create(
        string $name,
        int $collection_id,
        array $creative_ids
    ) {
        $group = new CollectionGroup();

        $group->name = $name;
        $group->collection_id = $collection_id;
        $group->save();

        $group->Service()->attachCreatives($creative_ids);

        return $group;
    }

    public function update(string $name)
    {
        $this->group->name = $name;
        $this->group->save();

        return $this->group->refresh();
    }

    public function syncCreatives(array $creative_ids = [])
    {
        if (empty($creative_ids)) {
            return $this->group->creatives()->detach();
        }

        return $this->group->creatives()->sync($creative_ids);
    }

    public function attachCreatives(array $creative_ids = [])
    {
        foreach ($creative_ids as $id) {
            if($creative = CollectionCreative::find($id)) {
                if(!$creative->image) {
                    $creative->Service()->uploadImage();
                }
                $group_creative = new CollectionGroupCreative;
                $group_creative->creative_id = $creative->id;
                $group_creative->type = $creative->type;
                $group_creative->group_id = $this->group->id;
                $group_creative->data = $creative->data;
                $group_creative->title = $creative->data['body_text'] ?? null;
                $group_creative->headline = $creative->data['headline'] ?? null;
                $group_creative->text = $creative->data['description'] ?? null;
                $group_creative->url = $creative->data['page_url'] ?? null;
                $group_creative->call_to_action = $creative->data['call_to_action'] ?? null;
                $group_creative->save();
            }
        }
        
        return $this->group;
    }

    public static function duplicate(
        string $group_name,
        int $collection_id,
        array $group_creatives
    )
    {
        $group = CollectionGroupService::create(
            $group_name,
            $collection_id,
            $group_creatives
        );

        return $group;
    }

    public function delete(): bool
    {
        if($this->group->groupCreatives->count() > 0) {
            $groupCreatives = $this->deleteGroupCreatives($this->group->groupCreatives->pluck('id')->toArray());
        }
        
        return $this->group->delete();
    }

    public function deleteGroupCreatives(array $group_creative_ids)
    {
        try {
            CollectionGroupCreative::whereIn('id', $group_creative_ids)->delete();
        } catch (\Throwable $th) {
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }
}
