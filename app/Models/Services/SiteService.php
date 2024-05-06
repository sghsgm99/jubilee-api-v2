<?php

namespace App\Models\Services;

use App\Models\Campaign;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Enums\SiteStatusEnum;
use App\Models\Enums\SitePlatformEnum;
use App\Traits\ImageModelServiceTrait;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;

class SiteService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var site
     */
    private $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
        $this->model = $site; // required
    }

    public static function create(
        User $user,
        string $name,
        string $url,
        string $client_key = null,
        string $client_secret_key = null,
        string $description,
        SitePlatformEnum $platform,
        SiteStatusEnum $status
    ) {
        $site = new Site();

        $site->name = $name;
        $site->url = $url;
        $site->client_key = $client_key;
        $site->client_secret_key = $client_secret_key;
        $site->description = $description;
        $site->platform = $platform;
        $site->status = $status;

        $site->user_id = $user->id;
        $site->account_id = $user->account_id;
        $site->save();

        $site_setting = new SiteSetting();
        $site_setting->title = $site->name;
        $site_setting->site_id = $site->id;
        $site_setting->account_id = $site->account_id;
        $site_setting->save();

        return $site->fresh();
    }

    public function update(
        string $name,
        string $url,
        string $client_key = null,
        string $client_secret_key = null,
        string $description,
        SitePlatformEnum $platform,
        SiteStatusEnum $status
    ) {
        $this->site->name = $name;
        $this->site->url = $url;
        $this->site->client_key = $client_key;
        $this->site->client_secret_key = $client_secret_key;
        $this->site->description = $description;
        $this->site->platform = $platform;
        $this->site->status = $status;

        $this->site->save();
        return $this->site->fresh();
    }

    public function updateProvisioning(
        string $host,
        string $ssh_username,
        string $ssh_password,
        string $path
    ) {
        $this->site->host = $host;
        $this->site->ssh_username = $ssh_username;
        $this->site->ssh_password = $ssh_password;
        $this->site->path = $path;

        $this->site->save();
        return $this->site->fresh();
    }

    public function updateAnalytics(
        string $view_id,
        //UploadedFile $analytic_file,
        //string $analytic_script
    ) {
        /*$file = $this->site->FileServiceFactory('analytics', StorageDiskEnum::LOCAL())
            ->uploadFile($analytic_file, 'service-account-credentials');*/

        $this->site->view_id = $view_id;
        //$this->site->analytic_file = $file['dir_path'];
        //$this->site->analytic_script = $analytic_script;

        $this->site->save();
        return $this->site->fresh();
    }

    public function uploadLogoFavicon(UploadedFile $file, string $filename = 'logo', string $dir = null)
    {
        $images = $this->site->images()->where('name', 'LIKE', "%{$filename}%")->get();
        foreach ($images as $image) {
            $this->deleteImage($image, $dir);
        }

        $image = $this->site->FileServiceFactory($dir)->uploadFile($file, $filename);

        ImageService::updateOrCreate($this->site, $file, $image['name']);

        return $image;
    }

    public function deployed(): bool
    {
        $client = new Client();
        /*$options = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'id' => $this->site->id,
                'env' => (env('APP_ENV') === 'production') ? 'production' : 'staging'
            ])
        ];*/

        
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . config('deployer.token')
            ],
            'body' => json_encode([
                'ref' => config('deployer.ref'),
                'inputs' => [
                    'apiroot' => config('deployer.apiroot'),
                    'host' => $this->site->host,
                    'path' => $this->site->path,
                    'theme' => $this->site->settings->siteTheme->handle ?? 'default',
                    'token' => $this->site->api_jubilee_key,
                    'username' => $this->site->ssh_username,
                    'password' => $this->site->ssh_password,
                    "serp" => $this->site->settings->style == 1 ? '' : 'serp'
                ]
            ])
        ];

        try {
            $response = $client->request('POST', config('deployer.url'), $options);
            //$response = $client->request('POST', config('deployer.site_endpoint'), $options);
        } catch (\Throwable $exception) {
            abort(500, 'Bad Request: ' . $exception->getMessage());
        }
        
        return true;
    }

    public function delete(): bool
    {
        if (Campaign::where('site_id', $this->site->id)->exists()) {
            return false;
        }

        $this->site->delete();
        return true;
    }

    public static function BulkDelete(array $ids)
    {
        $archived = [];
        $unarchived = [];

        foreach ($ids as $id) {
            $site = Site::find($id);
            if ($site->status->isNot(SiteStatusEnum::PUBLISHED())) {
                if (!$site->Service()->delete()) {
                    $unarchived[] = [
                        "id" => $id,
                        "title" => $site->title,
                        "status" => "Unarchived",
                        "message" => "Site cannot be deleted because of a relationship data with Campaigns"
                    ];
                } else {
                    $archived[] = [
                        "id" => $id,
                        "title" => $site->title,
                        "status" => "Archived",
                        "message" => "Site was archived."
                    ];
                }
            } else {
                $unarchived[] = [
                    "id" => $id,
                    "title" => $site->title,
                    "status" => "Published",
                    "message" => "Site cannot be deleted because the current status is PUBLISHED"
                ];
            }
        }

        $response = [
            "archived" => $archived,
            "unarchived" => $unarchived
        ];
        return $response;
    }

    public function uploadContentImages(array $images = [])
    {
        $files = [];
        foreach ($images as $image) {
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
            $files[] = $this->site->FileServiceFactory($this->site->getContentImagesDir())->uploadFile($image, $filename);
        }

        return $files;
    }
}
