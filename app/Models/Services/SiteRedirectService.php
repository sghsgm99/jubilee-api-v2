<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteRedirect;

class SiteRedirectService extends ModelService
{
    /**
     * @var SiteRedirect
     */
    private $site_redirect;

    public function __construct(SiteRedirect $site_redirect)
    {
        $this->site_redirect = $site_redirect;
        $this->model = $site_redirect;
    }

    public static function create(
        Site $site,
        string $source,
        string $destination,
        string $code
    )
    {
        $siteRedirect = new SiteRedirect();
        $siteRedirect->site_id = $site->id;
        $siteRedirect->source = $source;
        $siteRedirect->destination = $destination;
        $siteRedirect->code = $code;
        $siteRedirect->save();

        return $siteRedirect;
    }

    public function update(string $source, string $destination, string $code)
    {
        $this->site_redirect->source = $source;
        $this->site_redirect->destination = $destination;
        $this->site_redirect->code = $code;
        $this->site_redirect->save();

        return $this->site_redirect;
    }

    public static function bulkDelete(array $ids)
    {
        $deleted = [];
        foreach($ids as $id){
            if ($redirect = SiteRedirect::find($id)) {
                $deleted[] = [
                    'id' => $redirect->id,
                    'deleted' => $redirect->Service()->delete()
                ];
            }
        }
        return $deleted;
    }
}
