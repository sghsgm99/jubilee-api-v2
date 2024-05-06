<?php

namespace App\Models\Enums;

/**
 * @method static FACEBOOK()
 * @method static INSTAGRAM()
 * @method static MESSENGER()
 * @method static AUDIENCE_NETWORK()
 */
class FacebookPublisherPlatformEnum extends Enumerate
{
    const FACEBOOK = 'facebook';
    const INSTAGRAM = 'instagram';
    const MESSENGER = 'messenger';
    const AUDIENCE_NETWORK = 'audience_network';
}
