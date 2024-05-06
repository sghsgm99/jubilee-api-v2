<?php

namespace App\Models\Enums;

/**
 * @method static HOME()
 * @method static RECENT()
 * @method static TRAVEL_IN()
 */
class FacebookLocationTypeEnum extends Enumerate
{
    const HOME = 'home';
    const RECENT = 'recent';
    const TRAVEL_IN = 'travel_in';
}
