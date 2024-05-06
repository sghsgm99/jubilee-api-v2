<?php

namespace App\Models\Enums;

/**
 * @method static LIFETIME_BUDGET()
 * @method static DAILY_BUDGET()
 */
class FacebookBudgetTypeEnum extends Enumerate
{
    const LIFETIME_BUDGET = 'lifetime_budget';
    const DAILY_BUDGET = 'daily_budget';

}
