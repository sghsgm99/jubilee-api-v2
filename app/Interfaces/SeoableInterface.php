<?php

namespace App\Interfaces;

use App\Models\Seo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface SeoableInterface
{
    /**
     * Relationship to the Seo Model.
     *
     * @return MorphOne|Seo
     */
    public function seo();

}
