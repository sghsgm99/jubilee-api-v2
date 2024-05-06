<?php

namespace App\Models\Services;

use Illuminate\Support\Facades\Hash;
use App\Models\KeyIntl;
use App\Services\GoogleKeywordService;

class AdTemplateService extends ModelService
{
    public static function create(string $category, string $keyword, int $intl)
    {
        $ki = new KeyIntl();
        $ki->category = $category;
        $ki->keyword = $keyword;
        $ki->intl = $intl;
        $ki->save();

        return $ki;
    }

    public static function searchKeywordHigh(string $kw, int $lvalue)
    {
        $ggKeywordService = GoogleKeywordService::resolve(1);

        return $ggKeywordService->getKeywordHigh($kw, $lvalue);
    }
}
