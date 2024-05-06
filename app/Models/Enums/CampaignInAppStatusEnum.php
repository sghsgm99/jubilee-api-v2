<?php

namespace App\Models\Enums;

/**
 * @method static DRAFT()
 * @method static ONGOING()
 * @method static PAUSED()
 * @method static STOPPED()
 * @method static DONE()
 */
class CampaignInAppStatusEnum extends Enumerate
{
    const DRAFT = 1;
    const PUBLISH = 2;
    const PAUSED = 3;
    const DONE = 4;
}
