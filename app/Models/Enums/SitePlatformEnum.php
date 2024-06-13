<?php

namespace App\Models\Enums;

/**
 * @method static WORDPRESS()
 * @method static JUBILEE()
 * @method static OTHERS()
 */
class SitePlatformEnum extends Enumerate
{
    const WORDPRESS = 1;
    const JUBILEE = 2;
    const OTHERS = 3;
    const SHOPIFY = 4;
}
