<?php

namespace App\Models\Services;

use App\Http\Resources\OcodesResource;
use App\Models\GoogleReport;
use App\Models\Mroas;
use App\Models\Ocodes;
use App\Models\User;
use App\Models\Site;
use App\Models\Services\SiteService;
use App\Models\Enums\SiteStatusEnum;
use App\Models\Enums\SitePlatformEnum;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class MROASService extends ModelService
{

    /**
     * @var User
     */
    private $Ocodes;

    public function __construct(Ocodes $Ocodes)
    {
        $this->ocodes = $Ocodes;
        $this->model = $Ocodes;
    }

    public static function create(string $cid, string $intl, string $keyword)
    {
        $channelValue = "us" . $intl;
        $mroas = new Mroas();
        $mroas->cid = $cid;
        $mroas->intl = $channelValue;
        $mroas->keyword = $keyword;

        if ($mroas->save()) {
            $client = Ocodes::select('client_id')->where('ocode', $cid)->first();
            $googleReport = GoogleReport::whereJsonContains('data->client_id', $client->client_id)
                ->whereJsonContains('data->channel', $channelValue)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $cpc = trim(str_replace('$', '', $googleReport->data['cpc']));

        return ResponseService::success('Data was created succesfully.', [
            "mroas" => $mroas,
            "cpc" => $cpc
        ]);
    }

    public static function createOcodes(string $name, string $ocodes, string $client_id, Site $site)
    {
        $ocode = new Ocodes();
        $ocode->name = $name;
        $ocode->ocode = $ocodes;
        $ocode->client_id = $client_id;
        $ocode->site_id = $site->id;
        $ocode->save();

        return ResponseService::success('Ocode was created succesfully.', new OcodesResource($ocode));
    }

    public static function createBulkOcodes($rows, $user_id)
    {
        $user = User::findOrFail($user_id);

        $response = [];

        foreach($rows[0] as $index => $row){
            $parsedUrl = parse_url($row['site_url'], PHP_URL_HOST);
            $site = Site::whereUrl($parsedUrl)->first();

            if($site != null){
                $ifExist = Ocodes::where('name', '=', $row['name'])
                    ->where('ocode', '=', $row['ocode'])
                    ->where('client_id', '=', $row['client_id'])
                    ->first();

                if ($ifExist == null) {
                    $ocode = self::createOcodes(
                        $row['name'],
                        $row['ocode'],
                        $row['client_id'],
                        $site
                    );
                    $data = [
                        "message" => "Site already exist. Ocode was succesfully created.",
                        "ocode" => $ocode->original['data']
                    ];
                    $response[] = $data;
                }
            }else{
                $site = SiteService::create(
                    $user,
                    $parsedUrl,
                    $row['site_url'],
                    null,
                    null,
                    'Auto generated site from mroas',
                    SitePlatformEnum::OTHERS(),
                    SiteStatusEnum::DRAFT()
                );

                if ($site->id) {
                    $ocode = self::createOcodes(
                        $row['name'],
                        $row['ocode'],
                        $row['client_id'],
                        $site
                    );
                    $data = [
                        "message" => "Site and Ocode was succesfully created.",
                        "site" => $site,
                        "ocode" => $ocode->original
                    ];
                    $response[] = $data;
                }
            }
        }

        return $response;
    }

    public function updateOcodes(string $ocodes, string $client_id, Site $site)
    {
        $this->ocodes->name = "Adsense " . $client_id;
        $this->ocodes->ocode = $ocodes;
        $this->ocodes->client_id = $client_id;
        $this->ocodes->site_id = $site->id;
        $this->ocodes->save();

        return ResponseService::success('Ocode was updated succesfully.', new OcodesResource($this->ocodes));
    }

    public static function gloabalSearch(?string $search, int $paginate)
    {
        $ocodes = Ocodes::with(relations: 'site')
            ->where('name', 'like', '%' . $search . '%')
            ->orWhere('ocode', 'like', '%' . $search . '%')
            ->orWhere('client_id', 'like', '%' . $search . '%')
            ->paginate($paginate);

        return OcodesResource::collection($ocodes);
    }

    public static function bulkDelete(array $ids)
    {
        $successDelete = [];
        $failedDelete = [];

        foreach ($ids as $id) {
            if ($ocodes = Ocodes::find($id)) {
                $successDelete[] = new OcodesResource($ocodes);
                $ocodes->Service()->delete();
            } else {
                $failedDelete[] = $id;
            }
        }

        $response = [
            "message" => "Selected items are succesfully deleted",
            "deleted" => $successDelete,
            "undeleted" => $failedDelete
        ];
        return $response;
    }
}
