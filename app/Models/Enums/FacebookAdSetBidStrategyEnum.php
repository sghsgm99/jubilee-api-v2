<?php

namespace App\Models\Enums;

/**
 * @method static COST_CAP()
 * @method static LOWEST_COST_WITHOUT_CAP()
 * @method static LOWEST_COST_WITH_BID_CAP()
 */
class FacebookAdSetBidStrategyEnum extends Enumerate
{
    const COST_CAP = 'COST_CAP';
    const LOWEST_COST_WITHOUT_CAP = 'LOWEST_COST_WITHOUT_CAP';
    const LOWEST_COST_WITH_BID_CAP = 'LOWEST_COST_WITH_BID_CAP';

}
