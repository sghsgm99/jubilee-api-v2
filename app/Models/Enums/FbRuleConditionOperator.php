<?php

namespace App\Models\Enums;

/**
 * @method static OR()
 * @method static AND()
 */
class FbRuleConditionOperator extends Enumerate
{
    const OR = 0;
    const AND = 1;
}
