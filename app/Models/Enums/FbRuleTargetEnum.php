<?php

namespace App\Models\Enums;

/**
 * @method static CAMPAIGNS()
 * @method static ADSETS()
 * @method static ADS()
 */
class FbRuleTargetEnum extends Enumerate
{
    const CAMPAIGNS = 0;
    const ADSETS = 1;
    const ADS = 2;

    public function getFacebookStepLabel()
    {
        switch ($this->value) {
            case self::CAMPAIGNS:
                return 'campaign';

            case self::ADSETS:
                return 'adset';

            default:
                return 'ad';
        }
    }
}
