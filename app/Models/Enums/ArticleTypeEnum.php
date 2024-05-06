<?php

namespace App\Models\Enums;

/**
 * @method static POST()
 * @method static QUIZZES()
 * @method static INFINITE_SCROLL()
 * @method static GALLERY()
 */
class ArticleTypeEnum extends Enumerate
{
    const POST = 1;
    const QUIZZES = 2;
    const INFINITE_SCROLL = 3;
    const GALLERY = 4;
}
