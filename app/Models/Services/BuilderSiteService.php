<?php

namespace App\Models\Services;

use App\Models\BuilderSite;
use App\Traits\ImageModelServiceTrait;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class BuilderSiteService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var BuilderSite
     */
    private $builderSite;

    public function __construct(BuilderSite $builderSite)
    {
        $this->builderSite = $builderSite;
        $this->model = $builderSite; // required
    }

    public static function create(string $name, string $domain, string $seo = null): BuilderSite
    {
        $builder_site = new BuilderSite();
        $builder_site->name = $name;
        $builder_site->domain = $domain;
        $builder_site->seo = $seo;
        $builder_site->account_id = auth()->user()->account_id;
        $builder_site->save();

        return $builder_site;
    }

    public function update(
        string $name,
        string $domain,
        string $seo = null,
        string $preview_link = null
    ): BuilderSite
    {
        $this->builderSite->name = $name;
        $this->builderSite->domain = $domain;
        $this->builderSite->seo = $seo;
        $this->builderSite->preview_link = $preview_link;
        $this->builderSite->save();

        return $this->builderSite->fresh();
    }

    public function uploadLogoOrFavicon(UploadedFile $file, string $filename = 'logo'): array
    {
        $images = $this->builderSite->images()->where('name', 'LIKE', "%{$filename}%")->get();
        foreach ($images as $image) {
            $this->deleteImage($image);
        }

        $image = $this->builderSite->FileServiceFactory()->uploadFile($file, $filename);

        ImageService::create($this->builderSite, $file, $image['name']);

        return $image;
    }

    public function updateSettings(
        string $host,
        string $ssh_username,
        string $ssh_password,
        string $path
    )
    {
        $this->builderSite->host = $host;
        $this->builderSite->ssh_username = $ssh_username;
        $this->builderSite->ssh_password = $ssh_password;
        $this->builderSite->path = $path;
        $this->builderSite->save();

        return $this->builderSite->fresh();
    }

    public function generateToken()
    {
        $key = md5($this->builderSite->id . $this->builderSite->name . Carbon::now());

        $this->builderSite->api_builder_key = "builder_{$key}";
        $this->builderSite->save();

        return $this->builderSite->api_builder_key;
    }

    public function deployed(string $type = 'deploy')
    {
        $env = (env('APP_ENV') === 'production') ? 'production' : 'staging';
        $env = ($type === 'preview') ? 'preview' : $env;

        $options = [
            'id' => $this->builderSite->id,
            'env' => $env
        ];

        try {
            $response = Http::post(config('deployer.builder_endpoint'), $options);
            return json_decode($response->body());
        } catch (\Throwable $exception) {
            abort(500, 'Bad Request: ' . $exception->getMessage());
        }
    }
}
