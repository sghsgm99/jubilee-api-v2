<?php

namespace App\Models\Enums;

/**
 * @method static IS_LESS_THAN()
 * @method static EQUALS()
 * @method static IS_GREATER_THAN()
 */
class FbRuleConditionComparison extends Enumerate
{
    const IS_LESS_THAN = 0;
    const EQUALS = 1;
    const IS_GREATER_THAN = 2;
}
