<?php

namespace App\Models\Services;

use App\Models\BlackList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Enums\BlackListStatusEnum;
use App\Models\Enums\BlackListTypeEnum;

class BlackListService extends ModelService
{
    private $blacklist;

    public function __construct(BlackList $blacklist)
    {
        $this->blacklist = $blacklist;
        $this->model = $blacklist; // required
    }

    public static function create(
        User $user,
        string $name,
        string $domain = null,
        string $subdomain = null,
        BlackListTypeEnum $type,
        BlackListStatusEnum $status
    )
    {
        $blacklist = new BlackList();

        $blacklist->name = $name;
        $blacklist->domain = $domain;
        $blacklist->subdomain = $subdomain;
        $blacklist->type = $type;
        $blacklist->status = $status;
        $blacklist->user_id = $user->id;
        $blacklist->account_id = $user->account_id;
        $blacklist->save();
        
        return $blacklist;
    }

    public function update(
        string $name,
        string $domain = null,
        string $subdomain = null,
        BlackListTypeEnum $type,
        BlackListStatusEnum $status
    )
    {
        $this->blacklist->name = $name;
        $this->blacklist->domain = $domain;
        $this->blacklist->subdomain = $subdomain;
        $this->blacklist->type = $type;
        $this->blacklist->status = $status;
        $this->blacklist->save();

        return $this->blacklist->fresh();
    }

    public function delete() :bool
    {
        $this->blacklist->delete();
        return true;
    }
}
