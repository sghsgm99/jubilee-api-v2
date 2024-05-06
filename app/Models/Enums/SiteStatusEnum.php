<?php

namespace App\Models\Enums;

/**
 * @method static PUBLISHED()
 * @method static UNPUBLISHED()
 * @method static DRAFT()
 */
class SiteStatusEnum extends Enumerate
{
    const PUBLISHED = 1;
    const UNPUBLISHED = 2;
    const DRAFT = 3;
}
