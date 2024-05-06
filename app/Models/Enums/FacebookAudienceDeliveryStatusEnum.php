<?php

namespace App\Models\Enums;

/**
 * @method static READY()
 * @method static INACTIVE()
 * @method static DISABLED()
 */
class FacebookAudienceDeliveryStatusEnum extends Enumerate
{
    const READY = 200;
    const INACTIVE = 300;
    const DISABLED = 200;
}
