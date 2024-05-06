<?php

namespace App\Models\Enums;

/**
 * @method static GEOGRAPHY()
 * @method static DATE()
 * @method static REVENUE()
 * @method static VISITORS()
 */
class FbRuleConditionTarget extends Enumerate
{
    const GEOGRAPHY = 0;
    const DATE = 1;
    const REVENUE = 2;
    const VISITORS = 3;
}
