<?php

namespace App\Models\Enums;

/**
 * @method static STREAM()
 * @method static STORY()
 * @method static SHOP()
 * @method static EXPLORE()
 * @method static REELS()
 */
class FacebookInstagramPositionEnum extends Enumerate
{
    const STREAM = 'stream';
    const STORY = 'story';
    const SHOP = 'shop';
    const EXPLORE = 'explore';
    const REELS = 'reels';
}
