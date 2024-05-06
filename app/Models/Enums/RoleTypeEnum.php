<?php

namespace App\Models\Enums;

/**
 * @method static ROOT()
 * @method static ADMINISTRATOR()
 * @method static EDITOR()
 * @method static AUTHOR()
 */
class RoleTypeEnum extends Enumerate
{
    const ROOT = 1;
    const ADMINISTRATOR = 2;
    const EDITOR = 3;
    const AUTHOR = 4;
}
