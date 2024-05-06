<?php

namespace App\Models\Enums;

/**
 * @method static DRAFT()
 * @method static ONGOING()
 * @method static PAUSED()
 * @method static STOPPED()
 * @method static DONE()
 */
class CampaignStatusEnum extends Enumerate
{
    const DRAFT = 1;
    const ONGOING = 2;
    const PAUSED = 3;
    const STOPPED = 4;
    const DONE = 5;
}
