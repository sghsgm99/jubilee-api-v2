<?php

namespace App\Models\Enums;

/**
 * @method static PUBLISH()
 * @method static PRIVATE()
 * @method static DRAFT()
 */
class WordpressPostStatusEnum extends Enumerate
{
    const PUBLISH = ArticleStatusEnum::PUBLISHED;
    const PRIVATE = ArticleStatusEnum::UNPUBLISHED;
    const DRAFT = ArticleStatusEnum::DRAFT;
}
