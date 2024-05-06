<?php

namespace App\Models\Enums;

/**
 * @method static CREATE()
 * @method static READ()
 * @method static UPDATE()
 * @method static DELETE()
 */
class PermissionTypeEnum extends Enumerate
{
    const CREATE = 1;
    const READ = 2;
    const UPDATE = 3;
    const DELETE = 4;
}
