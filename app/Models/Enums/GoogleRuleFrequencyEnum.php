<?php

namespace App\Models\Enums;

class GoogleRuleFrequencyEnum extends Enumerate
{
    const RUN = 0;
    const ONCE = 1;
    const HOURLY = 2;
    const DAILY = 3;
    const WEEKLY = 4;
    const MONTHLY = 5;
}
