<?php

use App\Http\Controllers\Api\BuilderFileManagerController;
use App\Http\Controllers\Api\BuilderPageController;
use App\Http\Controllers\Api\BuilderSiteController;
use App\Http\Controllers\Api\CampaignTagController;
use App\Http\Controllers\Api\CMSBuilderSiteController;
use App\Http\Controllers\Api\FacebookAdController;
use App\Http\Controllers\Api\FacebookRuleAutomationController;
use App\Http\Controllers\Api\SiteAnalyticController;
use App\Http\Controllers\Api\SiteCategoryController;
use App\Http\Controllers\Api\SiteMenuController;
use App\Http\Controllers\Api\SiteTagController;
use App\Http\Controllers\Api\WordpressServiceController;
use App\Http\Middleware\BuilderToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AnalyticController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\CMSSiteController;
use App\Http\Controllers\Api\AdTemplateController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CManagerController;
use App\Http\Controllers\Api\AdPartnerController;
use App\Http\Controllers\Api\CopymaticController;
use App\Http\Controllers\Api\CopyscapeController;
use App\Http\Middleware\JubileeToken;
use App\Http\Controllers\Api\AdServiceController;
use App\Http\Controllers\Api\FacebookTwoTierController;
use App\Http\Controllers\Api\ReadableApiController;
use App\Http\Controllers\Api\SiteAdController;
use App\Http\Controllers\Api\AdBuilderController;
use App\Http\Controllers\Api\ArticleTypeController;
use App\Http\Controllers\Api\FacebookInterestController;
use App\Http\Controllers\Api\FacebookLookalikeController;
use App\Http\Controllers\Api\BlackListController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\RuleSetController;
use App\Http\Controllers\Api\KeywordSpinningController;
use App\Http\Controllers\Api\MROASController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\RoleSetupTemplateController;
use App\Http\Controllers\Api\SubDomainController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CollectionAdController;
use App\Http\Controllers\Api\GlobalAdvanceSearchController;
use App\Http\Controllers\Api\FacebookAdLibraryController;
use App\Http\Controllers\Api\FacebookAdsetController;
use App\Http\Controllers\Api\FacebookCampaignController;
use App\Http\Controllers\Api\RuleAutomationController;
use App\Http\Controllers\Api\SiteRedirectController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\CampaignAutoLauncherController;
use App\Http\Controllers\Api\AdLibraryController;
use App\Http\Controllers\Api\SitePageController;
use App\Http\Controllers\Api\JubileeTestController;
use App\Http\Controllers\Api\GoogleCampaignController;
use App\Http\Controllers\Api\GoogleAdgroupController;
use App\Http\Controllers\Api\GoogleAdController;
use App\Http\Controllers\Api\GoogleCustomerController;
use App\Http\Controllers\Api\GoogleImageController;
use App\Http\Controllers\Api\AICreatorController;
use App\Http\Controllers\Api\GoogleRuleAutomationController;
use App\Http\Controllers\Api\GoogleAICampaignController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Authenticated Routes
 */
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    /** Logout route */
    Route::post('logout', [AuthController::class, 'logout']);

    /** Fetch auth user **/
    Route::get('auth/user', [AuthController::class, 'authUser']);

    /** API Routes */
    UserController::apiRoutes();
    ArticleController::apiRoutes();
    ArticleTypeController::apiRoutes();
    CampaignController::apiRoutes();
    ChannelController::apiRoutes();
    AccountController::apiRoutes();
    SettingController::apiRoutes();
    SiteCategoryController::apiRoutes();
    SiteTagController::apiRoutes();
    SiteMenuController::apiRoutes();
    SiteController::apiRoutes();
    AnalyticController::apiRoutes();
    AdTemplateController::apiRoutes();
    ReportController::apiRoutes();
    CManagerController::apiRoutes();
    AdPartnerController::apiRoutes();
    SiteAdController::apiRoutes();
    SiteAnalyticController::apiRoutes();
    AdBuilderController::apiRoutes();
    BlackListController::apiRoutes();
    ContactUsController::apiRoutes();
    RuleSetController::apiRoutes();
    CampaignTagController::apiRoutes();
    KeywordSpinningController::apiRoutes();
    MROASController::apiRoutes();
    DomainController::apiRoutes();
    SubDomainController::apiRoutes();
    RoleSetupTemplateController::apiRoutes();
    GlobalAdvanceSearchController::apiRoutes();
    SiteRedirectController::apiRoutes();
    SuperAdminController::apiRoutes();
    CampaignAutoLauncherController::apiRoutes();
    AdLibraryController::apiRoutes();
    SitePageController::apiRoutes();

    /** Wordpress Routes */
    WordpressServiceController::apiRoutes();

    /** Creator Studios Routes  */
    CopymaticController::apiRoutes();
    CopyscapeController::apiRoutes();
    ReadableApiController::apiRoutes();

    /** API Deployer Routes CMS */
    // CMSSiteController::apiRoutes();

    /* Facebook 2-Tier */
    FacebookTwoTierController::apiRoutes();
    FacebookCampaignController::apiRoutes();
    FacebookAdsetController::apiRoutes();
    FacebookAdController::apiRoutes();

    /** Facebook Interest */
    FacebookInterestController::apiRoutes();

    /** Facebook Lookalike */
    FacebookLookalikeController::apiRoutes();

    /** Facebook Ads Library */
    FacebookAdLibraryController::apiRoutes();

    /** Facebook Rule Automations */
    FacebookRuleAutomationController::apiRoutes();

    /** Collection Libaray */
    CollectionController::apiRoutes();
    CollectionAdController::apiRoutes();

    BuilderSiteController::apiRoutes();
    BuilderPageController::apiRoutes();
    BuilderFileManagerController::apiRoutes();

    GoogleCampaignController::apiRoutes();
    GoogleAdgroupController::apiRoutes();
    GoogleAdController::apiRoutes();
    GoogleCustomerController::apiRoutes();
    GoogleImageController::apiRoutes();
    AICreatorController::apiRoutes();
    GoogleRuleAutomationController::apiRoutes();
    GoogleAICampaignController::apiRoutes();
});

/**
 * Auth Routes
 */
Route::prefix('v1')->group(function () {
    /** Login route */
    Route::post('login', [AuthController::class, 'login']);

    /** Register route */
    Route::post('register', [AuthController::class, 'register']);

    /** Reset Password routes */

    /* test accept code from FB get permission */
    Route::get('facebook/accept-code', [ChannelController::class, 'acceptCode']);

    /* test get-permission for FB integration */
    Route::get('channels/get-permission/{channel}', [ChannelController::class, 'getPermission'])->name('get-permission');

    /* API Deployer Routes CMS — webhook require jubilee token request */
    Route::middleware([JubileeToken::class])->group(function () {
        CMSSiteController::webhooks();
        AdServiceController::webhooks();
    });

    /* API Deployer Routes CMS Site builder require builder token request */
    Route::middleware([BuilderToken::class])->group(function () {
        CMSBuilderSiteController::webhooks();
    });

    /** Account Registration */
    AccountController::unguardedRoutes();

    /** Contact Us Route */
    ContactUsController::unguardedRoutes();

    /** MROAS Route */
    MROASController::unguardedRoutes();
    
    Route::get('test', [JubileeTestController::class, 'test']);
});
