<?php

namespace App\Models\Enums;

use App\Models\Enums\Enumerate;

/**
 * @method static ACTIVE()
 * @method static ARCHIVED()
 * @method static DELETED()
 * @method static PAUSED()
 */
class FacebookCampaignStatusEnum extends Enumerate
{
    const ACTIVE = 'ACTIVE';
    const ARCHIVED = 'ARCHIVED';
    const DELETED = 'DELETED';
    const PAUSED = 'PAUSED';

}
