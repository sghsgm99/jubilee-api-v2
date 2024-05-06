<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Faker\Guesser\Name;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class FacebookTwoTierController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('2-tier/check', [FacebookTwoTierController::class, 'check']);
        Route::get('2-tier/set-primary-page', [FacebookTwoTierController::class, 'setPrimaryPage']);
        Route::get('2-tier/set-user-to-child-bm/{channel}', [FacebookTwoTierController::class, 'setUserToBM']);
    }

    public function check()
    {
        // FACEBOOK_APP_SECRET=f72931a8f413e19d6b482de410107dd2
        // FACEBOOK_BUSINESS_MANAGER_ID=
        // FACEBOOK_ACCESS_TOKEN=EAANwfRawZBYoBAF8aoK5bQp8zv0Hz0PFbQXEcZCX0GZCZC9lnwPCGj7bnIzsYmQ7NmMh6aPhOhxZC3MlYl8c1y37cejL2wbYsZAZANX15l6ZCpJhZBJFAGZCz7ClHgNZCYbYRkgMXVl2ZCDTrtcqoazbnIaDUlRUvUE2HPhSRxEeRuZBZB9Ldq0lwgHgZA5
        // FACEBOOK_CLIENT_ID=215773820696883
        // FACEBOOK_APP_ID=968107484051850

        // Take notes
        // Bussines_id = 379482297110774 (Disrupt Social)

        // create business manager
        // name = "test bm"
        // vertical = "ADVERTISING"
        // primary_page = "111428824679397"
        // timezone_id = 1
        // access_token = EAANwfRawZBYoBAF8aoK5bQp8zv0Hz0PFbQXEcZCX0GZCZC9lnwPCGj7bnIzsYmQ7NmMh6aPhOhxZC3MlYl8c1y37cejL2wbYsZAZANX15l6ZCpJhZBJFAGZCz7ClHgNZCYbYRkgMXVl2ZCDTrtcqoazbnIaDUlRUvUE2HPhSRxEeRuZBZB9Ldq0lwgHgZA5

        // dd('test');

        $res = Http::post('https://graph.facebook.com/v12.0/968107484051850/businesses', [
            'name' => 'test bm',
            'vertical' => 'ADVERTISING',
            'primary_page' => '111428824679397',
            'timezone_id' => '1',
            'access_token' => 'EAANwfRawZBYoBAF8aoK5bQp8zv0Hz0PFbQXEcZCX0GZCZC9lnwPCGj7bnIzsYmQ7NmMh6aPhOhxZC3MlYl8c1y37cejL2wbYsZAZANX15l6ZCpJhZBJFAGZCz7ClHgNZCYbYRkgMXVl2ZCDTrtcqoazbnIaDUlRUvUE2HPhSRxEeRuZBZB9Ldq0lwgHgZA5'
        ]);

        dd($res->json());
    }

    public function setPrimaryPage()
    {

        $account = Auth::user()->account;
        $appSecretProof = hash_hmac('sha256', $account->facebook_access_token, $account->facebook_app_secret);

        // dd($account);

        $res = Http::post("https://graph.facebook.com/v12.0/{$account->facebook_business_manager_id}", [
            'primary_page' => '102135372173928',
            'access_token' => $account->facebook_access_token,
            'appsecret_proof' => $appSecretProof,
        ]);

        dd($res->json());
    }

    public function setUserToBM(Channel $channel)
    {
        $account = Auth::user()->account;
        $appSecretProof = hash_hmac('sha256', $account->facebook_access_token, $account->facebook_app_secret);

        // dd($channel->channelFacebook->child_business_manager_id);


        // fetch the access token of the sustem user of the child BM
        // $res = Http::post("https://graph.facebook.com/v12.0/{$channel->channelFacebook->child_business_manager_id}/access_token", [
        //     'app_id' => $account->facebook_app_id,
        //     'scope' => "ads_management,business_management,ads_read,read_insights",
        //     'access_token' => $account->facebook_access_token,
        //     'appsecret_proof' => $appSecretProof,
        // ]);

        // dd($res->json());

        // if(!$res->ok()) {
        //     dd($res->json());
        // }

        // $access_token = $res->json()['access_token'];
        // $appSecretProof2 = hash_hmac('sha256', $access_token, $account->facebook_app_secret);

        // Add a user to the child Business Manager.
        // $res = Http::post("https://graph.facebook.com/v12.0/{$channel->channelFacebook->child_business_manager_id}/business_users", [
        //     'email' => "Alexandra93Ramos31@gmail.com",
        //     'role' => "EMPLOYEE",
        //     'tasks' => ['ADVERTISE', 'ANALYZE'],
        //     'access_token' => $account->facebook_access_token,
        //     'appsecret_proof' => $appSecretProof,
        // ]);


        // $res = Http::get("https://graph.facebook.com/v12.0/{$channel->channelFacebook->child_business_manager_id}/owned_ad_accounts", [
        //     'access_token' => $account->facebook_access_token,
        //     'appsecret_proof' => $appSecretProof,
        // ]);


        // $res = Http::get("https://graph.facebook.com/v12.0/act_423500162507474/campaigns", [
        //     'access_token' => $account->facebook_access_token,
        //     'appsecret_proof' => $appSecretProof,
        // ]);
        
        
        $res = Http::post("https://graph.facebook.com/v12.0/23849943274120413/", [
            'status' => 'PAUSED',
            'access_token' => $account->facebook_access_token,
            'appsecret_proof' => $appSecretProof,
        ]);


        // 23849943274120413


        dd($res->json());

        if(!$res->ok()) {
            dd($res->json());
        }

    }

}
