<?php

namespace App\Models\Enums;

/**
 * @method static FEED()
 * @method static RIGHT_HAND_COLUMN()
 * @method static INSTANT_ARTICLE()
 * @method static MARKETPLACE()
 * @method static VIDEO_FEEDS()
 * @method static STORY()
 * @method static SEARCH()
 * @method static INSTREAM_VIDEO()
 */
class FacebookFacebookPositionEnum extends Enumerate
{
    const FEED = 'feed';
    const RIGHT_HAND_COLUMN = 'right_hand_column';
    const INSTANT_ARTICLE = 'instant_article';
    const MARKETPLACE = 'marketplace';
    const VIDEO_FEEDS = 'video_feeds';
    const STORY = 'story';
    const SEARCH = 'search';
    const INSTREAM_VIDEO = 'instream_video';
}
