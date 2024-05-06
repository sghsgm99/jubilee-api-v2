<?php

namespace App\Models\Enums;

/**
 * @method static APP_INSTALLS()
 * @method static CLICKS()
 * @method static IMPRESSIONS()
 * @method static LINK_CLICKS()
 * @method static LISTING_INTERACTION()
 * @method static NONE()
 * @method static OFFER_CLAIMS()
 * @method static PAGE_LIKES()
 * @method static POST_ENGAGEMENT()
 * @method static PURCHASE()
 * @method static THRUPLAY()
 */
class FacebookAdSetBillingEventEnum extends Enumerate
{
    const APP_INSTALLS = 'APP_INSTALLS';
    const CLICKS = 'CLICKS';
    const IMPRESSIONS = 'IMPRESSIONS';
    const LINK_CLICKS = 'LINK_CLICKS';
    const LISTING_INTERACTION = 'LISTING_INTERACTION';
    const NONE = 'NONE';
    const OFFER_CLAIMS = 'OFFER_CLAIMS';
    const PAGE_LIKES = 'PAGE_LIKES';
    const POST_ENGAGEMENT = 'POST_ENGAGEMENT';
    const PURCHASE = 'PURCHASE';
    const THRUPLAY = 'THRUPLAY';
}
