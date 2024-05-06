<?php

namespace App\Models\Enums;

/**
 * @method static LEARN_MORE()
 * @method static GET_QUOTE()
 * @method static GET_OFFER()
 * @method static DOWNLOAD()
 * @method static CONTACT_US()
 * @method static BOOK_NOW()
 * @method static APPLY_NOW()
 * @method static GET_SHOWTIMES()
 */
class FacebookCallToActionEnum extends Enumerate
{
    const LEARN_MORE = 'LEARN_MORE';
    const GET_QUOTE = 'GET_QUOTE';
    const GET_OFFER = 'GET_OFFER';
    const DOWNLOAD = 'DOWNLOAD';
    const CONTACT_US = 'CONTACT_US';
    const OPEN_LINK = 'OPEN_LINK';
    const APPLY_NOW = 'APPLY_NOW';
    const GET_SHOWTIMES = 'GET_SHOWTIMES';
}
