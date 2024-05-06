<?php

namespace App\Models\Enums;

/**
 * @method static BLOCK()
 * @method static UNBLOCK()
 */
class BlackListStatusEnum extends Enumerate
{
    const BLOCK = 1;
    const UNBLOCK = 2;
}
