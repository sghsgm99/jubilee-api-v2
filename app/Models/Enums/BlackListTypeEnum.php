<?php

namespace App\Models\Enums;

/**
 * @method static DOMAIN()
 * @method static SUBDOMAIN()
 * @method static SUBDOMAIN_DOMAIN()
 */
class BlackListTypeEnum extends Enumerate
{
    const DOMAIN = 1;
    const SUBDOMAIN = 2;
    const SUBDOMAIN_DOMAIN = 3;
}
