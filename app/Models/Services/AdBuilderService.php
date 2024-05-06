<?php

namespace App\Models\Services;

use App\Models\AdBuilder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdBuilderService extends ModelService
{
    private $adbuilder;

    public function __construct(AdBuilder $adbuilder)
    {
        $this->adbuilder = $adbuilder;
        $this->model = $adbuilder;
    }

    public static function create(
        string $name,
        string $url,
        string $gjs_components = null,
        string $gjs_style = null,
        string $gjs_html = null,
        string $gjs_css = null
    )
    {   
        $adbuilder = new AdBuilder();

        $adbuilder->name = $name;
        $adbuilder->url = $url;
        $adbuilder->gjs_components = $gjs_components;
        $adbuilder->gjs_style = $gjs_style;
        $adbuilder->gjs_html = $gjs_html;
        $adbuilder->gjs_css = $gjs_css;

        $adbuilder->save();

        return $adbuilder;
    }

    public function update(
        string $gjs_components = null,
        string $gjs_style = null,
        string $gjs_html,
        string $gjs_css = null
    )
    {

        $this->adbuilder->gjs_components = $gjs_components;
        $this->adbuilder->gjs_style = $gjs_style;
        $this->adbuilder->gjs_html = $gjs_html;
        $this->adbuilder->gjs_css = $gjs_css;

        $this->adbuilder->save();
        return $this->adbuilder->fresh();
    }
}
