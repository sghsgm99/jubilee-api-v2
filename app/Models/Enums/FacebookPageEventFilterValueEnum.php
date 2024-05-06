<?php

namespace App\Models\Enums;

/**
 * @method static page_engaged()
 * @method static page_visited()
 * @method static page_liked()
 * @method static page_messaged()
 * @method static page_cta_clicked()
 * @method static page_or_post_save()
 * @method static page_post_interaction()
 */
class FacebookPageEventFilterValueEnum extends Enumerate
{
    const page_engaged = 'Everyone who engaged with your Page';
    const page_visited = 'Anyone who visited your Page';
    const page_liked = 'People who currently like or follow your Page';
    const page_messaged = 'People who sent a message to your Page';
    const page_cta_clicked = 'People who clicked any call-to-action button';
    const page_or_post_save = 'People who saved your Page or any post';
    const page_post_interaction = 'People who engaged with any post or ad';
}
