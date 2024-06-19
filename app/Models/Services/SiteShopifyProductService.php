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
        int $pid,
        string $title,
        string $status,
        string $vendor,
        string $type,
        string $image
    )
    {
        $site_shopify_product = new SiteShopifyProduct();

        $site_shopify_product->site_id = $site->id;
        $site_shopify_product->user_id = Auth::user()->id;
        $site_shopify_product->account_id = Auth::user()->account_id;
        $site_shopify_product->pid = $pid;
        $site_shopify_product->title = $title;
        $site_shopify_product->status = $status;
        $site_shopify_product->vendor = $vendor;
        $site_shopify_product->type = $type;
        $site_shopify_product->image = $image;

        $site_shopify_product->save();

        return $site_shopify_product;
    }
}
