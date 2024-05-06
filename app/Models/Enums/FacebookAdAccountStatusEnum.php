<?php

namespace App\Models\Enums;

/**
 * @method static ACTIVE()
 * @method static DISABLED()
 * @method static UNSETTLED()
 * @method static PENDING_RISK_REVIEW()
 * @method static PENDING_SETTLEMENT()
 * @method static IN_GRACE_PERIOD()
 * @method static PENDING_CLOSURE()
 * @method static CLOSED()
 * @method static ANY_ACTIVE()
 * @method static ANY_CLOSED()
 */
class FacebookAdAccountStatusEnum extends Enumerate
{
    const ACTIVE = 1;
    const DISABLED = 2;
    const UNSETTLED = 3;
    const PENDING_RISK_REVIEW = 7;
    const PENDING_SETTLEMENT = 8;
    const IN_GRACE_PERIOD = 9;
    const PENDING_CLOSURE = 100;
    const CLOSED = 101;
    const ANY_ACTIVE = 201;
    const ANY_CLOSED = 202;
}
