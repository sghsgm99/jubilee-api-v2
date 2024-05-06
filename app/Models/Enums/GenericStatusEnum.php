<?php

namespace App\Models\Enums;

/**
 * @method static ACTIVE()
 * @method static INACTIVE()
 * @method static DELETED()
 */
class GenericStatusEnum extends Enumerate
{
    const ACTIVE = 1;
    const INACTIVE = 2;
    const DELETED = 3;
}
