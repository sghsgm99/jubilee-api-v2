<?php

namespace App\Models\Enums;

/**
 * @method static ACTIVE()
 * @method static INACTIVE()
 */
class DNSStatusEnum extends Enumerate
{
    const ACTIVE = 1;
    const INACTIVE = 2;
}
