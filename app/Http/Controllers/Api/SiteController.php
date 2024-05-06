<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CreateSiteThemeRequest;
use App\Http\Requests\UpdateSiteAnalyticRequest;
use App\Http\Requests\UploadSiteLogoRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\SiteAnalyticsResource;
use App\Models\Services\SiteThemeService;
use App\Models\SiteTheme;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use App\Http\Resources\SiteResourceProvisioning;
use App\Models\Services\SiteService;
use App\Models\Site;
use App\Models\User;
use App\Models\Enums\SiteStatusEnum;
use App\Models\Enums\SitePlatformEnum;
use App\Http\Requests\CreateSiteRequest;
use App\Http\Requests\DeleteMultipleSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Http\Requests\UpdateSiteThemeRequest;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('sites', [SiteController::class, 'create']);
        Route::post('sites/{site}/upload-favicon', [SiteController::class, 'uploadFavicon']);
        Route::post('sites/{site}/upload-logo', [SiteController::class, 'uploadLogo']);
        Route::put('sites/{site}', [SiteController::class, 'update']);
        Route::delete('sites/{site}/remove-logo-favicon/{image_id}', [SiteController::class, 'deleteLogoFavicon']);
        Route::delete('sites/delete', [SiteController::class, 'deleteMultiple']);
        Route::delete('sites/{site}', [SiteController::class, 'delete']);

        // site jubilee provisioning settings
        Route::put('sites/{site}/provisioning', [SiteController::class, 'updateProvisioning']);
        Route::get('sites/{site}/provisioning', [SiteController::class, 'getProvisioning']);

        // site jubilee settings
        Route::put('sites/{site}/settings', [SiteController::class, 'updateSettings']);
        Route::get('sites/{site}/settings', [SiteController::class, 'getSettings']);
        Route::get('sites/{site}/settings/toggle-index', [SiteController::class, 'toggleIndex']);

        // site jubilee theme
        Route::get('sites/themes', [SiteController::class, 'getThemes']);
        Route::post('sites/themes', [SiteController::class, 'createTheme']);
        Route::put('sites/themes/{siteTheme}', [SiteController::class, 'updateTheme']);
        Route::put('sites/themes/{siteTheme}/toogle-status', [SiteController::class, 'toggleTheme']);
        Route::delete('sites/themes/{siteTheme}', [SiteController::class, 'deleteTheme']);

        // site jubilee generate key
        Route::get('sites/{site}/generate-key', [SiteController::class, 'generateJubileeAPIKey']);
        Route::get('sites/{site}/deploy', [SiteController::class, 'deploy']);

        // site google analytics
        Route::post('sites/{site}/analytics', [SiteController::class, 'updateAnalytics']);
        Route::get('sites/{site}/analytics', [SiteController::class, 'getAnalytics']);

        Route::get('categories/{sitecategory}/articles', [SiteCategoryController::class, 'getCategoryArticles']);
        Route::get('sites/list-option', [SiteController::class, 'getSites']);
        Route::get('sites/{site}/categories', [SiteController::class, 'getSiteCategories']);
        Route::get('sites/{site}/tags', [SiteController::class, 'getSiteTags']);
        Route::get('sites/{site}/articles', [SiteController::class, 'getSiteArticles']);
        Route::get('sites/{site}', [SiteController::class, 'get']);
        Route::get('sites', [SiteController::class, 'getCollection']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $platform = SitePlatformEnum::memberByValue($request->input('platform', null));
        $status = SiteStatusEnum::memberByValue($request->input('status', null));
        $owner = $request->input('owner', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $sites = Site::search($search, $platform, $status, $owner, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return SiteResource::collection($sites);
    }

    public function get(Site $site)
    {
        return ResponseService::success('Success', new SiteResource($site));
    }

    public function getSiteCategories(Site $site)
    {
        return ResponseService::success('Success', $site->categories->all());
    }

    public function getSiteTags(Site $site)
    {
        return ResponseService::success('Success', $site->tags->all());
    }

    public function getSiteArticles(Request $request, Site $site)
    {
        return ArticleResource::collection(
            $site->articles()->paginate($request->input('per_page', 10))
        );
    }

    public function getThemes()
    {
        return SiteTheme::whereActive()->get(['id', 'title']);
    }

    public function toggleTheme(SiteTheme $siteTheme)
    {
        return ResponseService::success('Site theme status updated', $siteTheme->Service()->toggleThemeStatus());
    }

    public function createTheme(CreateSiteThemeRequest $request)
    {
        $siteTheme = SiteThemeService::create(
            $request->validated()['title'],
            $request->validated()['handle'],
            $request->validated()['description'] ?? null,
        );

        return ResponseService::success('Site theme was created.', $siteTheme);
    }

    public function updateTheme(UpdateSiteThemeRequest $request, SiteTheme $siteTheme)
    {
        $siteTheme->Service()->update(
            $request->validated()['title'],
            $request->validated()['handle'],
            $request->validated()['status'],
            $request->validated()['description'] ?? null,
        );

        return ResponseService::success('Site theme was updated.', $siteTheme);
    }

    public function deleteTheme(SiteTheme $siteTheme)
    {
        $siteTheme->Service()->forceDelete();

        return ResponseService::success('Site theme was removed.');
    }

    public function generateJubileeAPIKey(Site $site)
    {
        $prefix = 'jubilee_';
        $key = md5($site->id . $site->name . Carbon::now());
        $api_jubilee_key = $prefix . $key;

        $site->api_jubilee_key = $api_jubilee_key;
        $site->save();

        return ResponseService::success('Success', $site);
    }

    public function deploy(Site $site)
    {
        $site->Service()->deployed();

        return ResponseService::success('Site deployed. It will take at least 3-5 minutes to propagate.');
    }

    /**
     * Provisioning
     */
    public function getProvisioning(Request $request, Site $site)
    {
        return ResponseService::success('Success.', new SiteResourceProvisioning($site));
    }

    public function updateProvisioning(Request $request, Site $site)
    {
        $site = $site->Service()->updateProvisioning(
            $request->input('host'),
            $request->input('ssh_username'),
            $request->input('ssh_password'),
            $request->input('path'),
        );

        return ResponseService::success('Site Provision was updated.', new SiteResourceProvisioning($site));
    }

    /**
     * Settings
     */
    public function updateSettings(Request $request, Site $site)
    {
        $setting = $site->settings()->first();
        $settings = $setting->Service()->update(
            $request->input('title'),
            $request->input('description'),
            $request->input('theme_id'),
            $request->input('about_us_blurb'),
            $request->input('contact_us_blurb'),
            $request->input('status'),
            $request->input('is_index') ?? 1,
            $request->input('header_tags') ?? null,
            $request->input('body_tags') ?? null,
            $request->input('footer_tags') ?? null,
            $request->input('style')
        );

        return ResponseService::success('Site Setting was updated.', new SiteResource($site));
    }

    public function getSettings(Request $request, Site $site)
    {
        $settings = $site->settings()->first();
        return ResponseService::success('Success', $settings);
    }

    public function toggleIndex(Site $site)
    {
        $setting = $site->settings()->first();
        $settings = $setting->Service()->toggleIndex();

        return ResponseService::success('Success', $settings);
    }

    public function getSites(Request $request)
    {
        $keyword = $request->get('keyword', null);

        $query = Site::whereAccountId(auth()->user()->account_id);

        if ($keyword) {
            $query->where('name', 'LIKE', "%{$keyword}%");
        }

        $sites = $query->get(['id', 'name', 'platform'])->toArray();

        return ResponseService::success('Success', $sites);
    }

    public function getAnalytics(Site $site)
    {
        return ResponseService::success('Success', new SiteAnalyticsResource($site));
    }

    /**
     * Site Function
     */
    public function create(CreateSiteRequest $request)
    {
        $user = User::findOrFail($request['user_id']);
        $site = SiteService::create(
            $user,
            $request->validated()['name'],
            $request->validated()['url'],
            $request->validated()['client_key'] ?? null,
            $request->validated()['client_secret_key'] ?? null,
            $request->validated()['description'],
            SitePlatformEnum::memberByValue($request->validated()['platform']),
            SiteStatusEnum::memberByValue($request->validated()['status'])
        );

        return ResponseService::successCreate('Site was created.', new SiteResource($site));
    }

    public function uploadFavicon(UploadSiteLogoRequest $request, Site $site)
    {
        $image = $site->Service()->uploadLogoFavicon($request->file('image'), 'favicon', 'favicon');

        return ResponseService::success('Site favicon was uploaded.', $image);
    }

    public function uploadLogo(UploadSiteLogoRequest $request, Site $site)
    {
        $image = $site->Service()->uploadLogoFavicon($request->file('image'), 'logo', 'logo');

        return ResponseService::success('Site favicon was uploaded.', $image);
    }

    public function update(UpdateSiteRequest $request, Site $site)
    {
        $site = $site->Service()->update(
            $request->validated()['name'],
            $request->validated()['url'],
            $request->validated()['client_key'] ?? null,
            $request->validated()['client_secret_key'] ?? null,
            $request->validated()['description'],
            SitePlatformEnum::memberByValue($request->validated()['platform']),
            SiteStatusEnum::memberByValue($request->validated()['status'])
        );

        return ResponseService::success('Site was updated.', new SiteResource($site));
    }

    public function updateAnalytics(Request $request, Site $site)
    {
        $site = $site->Service()->updateAnalytics(
            $request->input('view_id'),
            //$request->file('analytic_file'),
            //$request->validated()['analytic_script'],
        );

        return ResponseService::success('Site analytics was updated.', new SiteAnalyticsResource($site));
    }

    public function deleteLogoFavicon(Site $site, int $image_id)
    {
        if ($image = $site->images()->where('id', $image_id)->first()) {
            $site->Service()->detachImage($image->id);
        }

        return ResponseService::success('Site image was deleted.');
    }

    public function deleteMultiple(DeleteMultipleSiteRequest $request)
    {
        return SiteService::BulkDelete($request->validated()['ids']);
    }

    public function delete(Site $site)
    {
        if (!$site->Service()->delete()) {
            return ResponseService::serverError('Site cannot be deleted because of a relationship data with Campaigns');
        }

        return ResponseService::success('Site was archived.');
    }
}
