<?php

namespace App\Models\Services;

use App\Models\RuleSet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Enums\RuleSetTypeEnum;

class RuleSetService extends ModelService
{
    private $ruleset;

    public function __construct(RuleSet $ruleset)
    {
        $this->ruleset = $ruleset;
        $this->model = $ruleset; // required
    }

    public static function create(
        User $user,
        string $name,
        RuleSetTypeEnum $type,
        string $advertiser,
        float $traffic_per,
        bool $turn_state,
        array $schedule,
        array $button = null
    )
    {
        $ruleset = new RuleSet();

        $ruleset->name = $name;
        $ruleset->type = $type;
        $ruleset->advertiser = $advertiser;
        $ruleset->traffic_per = $traffic_per;
        $ruleset->turn_state = $turn_state;
        $ruleset->schedule = $schedule;
        $ruleset->button = $button;
        $ruleset->user_id = $user->id;
        $ruleset->account_id = $user->account_id;
        $ruleset->save();
        
        return $ruleset;
    }

    public function update(
        string $name,
        RuleSetTypeEnum $type,
        string $advertiser,
        float $traffic_per,
        bool $turn_state,
        array $schedule,
        array $button = null
    )
    {
        $this->ruleset->name = $name;
        $this->ruleset->type = $type;
        $this->ruleset->advertiser = $advertiser;
        $this->ruleset->traffic_per = $traffic_per;
        $this->ruleset->turn_state = $turn_state;
        $this->ruleset->schedule = $schedule;
        $this->ruleset->button = $button;
        $this->ruleset->save();

        return $this->ruleset->fresh();
    }

    public function delete() :bool
    {
        $this->ruleset->delete();
        return true;
    }
}
