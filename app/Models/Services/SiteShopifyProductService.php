<?php

namespace App\Models\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Site;
use App\Models\SiteShopifyProduct;

class SiteShopifyProductService extends ModelService
{
    private $site_shopify_product;

    public function __construct(SiteShopifyProduct $site_shopify_product)
    {
        $this->site_shopify_product = $site_shopify_product;
        $this->model = $site_shopify_product; // required
    }

    public static function create(
        Site $site,
        array $data = null
    )
    {
        $site_shopify_product = new SiteShopifyProduct();

        $site_shopify_product->site_id = $site->id;
        $site_shopify_product->user_id = Auth::user()->id;
        $site_shopify_product->account_id = Auth::user()->account_id;
        $site_shopify_product->data = $data;

        $site_shopify_product->save();

        return $site_shopify_product;
    }
}
