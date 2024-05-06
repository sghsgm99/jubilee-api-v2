<?php

namespace App\Models\Enums;

/**
 * @method static USER_LIST()
 * @method static PAGE()
 * @method static APP()
 * @method static WEBSITE()
 * @method static OFFLINE()
 * @method static LOOKALIKE()
 */
class FacebookAudienceTypeEnum extends Enumerate
{
    const USER_LIST = 'USER_LIST';
    const PAGE = 'PAGE';
    const APP = 'APP';
    const WEBSITE = 'WEBSITE';
    const OFFLINE = 'OFFLINE';
    const LOOKALIKE = 'LOOKALIKE';
}
