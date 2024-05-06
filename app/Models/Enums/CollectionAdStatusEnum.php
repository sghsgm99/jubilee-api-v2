<?php

namespace App\Models\Enums;

/**
 * @method static PUBLISHED()
 * @method static DRAFT()
 */
class CollectionAdStatusEnum extends Enumerate
{
    const DRAFT = 1;
    const PUBLISHED = 2;
}
