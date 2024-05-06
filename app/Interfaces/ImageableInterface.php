<?php

namespace App\Interfaces;

use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface ImageableInterface
{
    /**
     * @param string|null $dir
     * @return string
     */
    public function getRootDestinationPath(string $dir = null): string;

    /**
     * Relationship to the Image Model.
     *
     * @return MorphOne|Image
     */
    public function image();

    /**
     * Relationship to the Image Model.
     *
     * @return MorphMany|Image
     */
    public function images();
}
