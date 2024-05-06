<?php

namespace App\Models\Services;

use App\Models\Channel;
use App\Models\ChannelFacebook;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\FacebookAdAccountStatusEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Enums\FacebookTimezoneEnum;
use App\Models\Enums\FacebookVerticalEnum;
use App\Services\FacebookAdSetService;
use App\Services\FacebookCampaignService;
use App\Services\FacebookChildBMService;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ChannelFacebookService extends ModelService
{

    /**
     * @var ChannelFacebook
     */
    private $channel_facebook;

    public function __construct(ChannelFacebook $channel_facebook)
    {
        $this->channel_facebook = $channel_facebook;
        $this->model = $channel_facebook; // required
    }

    private static function getUrl()
    {
        return 'https://graph.facebook.com/'.config('facebook.version'). '/';
    }

    public static function create(
        Channel $channel,
        string $fb_name = null,
        string $user_access_token = null,
        string $fb_page_id = null,
        string $fb_user_id = null,
        FacebookVerticalEnum $fb_vertical = null,
        FacebookTimezoneEnum $fb_timezone = null,
        string $fb_ad_account = null,
        string $fb_access_token = null,
        ChannelFacebookTypeEnum $type
    ) {
        $channel_facebook = new ChannelFacebook();
        $channel_facebook->name = $fb_name;
        $channel_facebook->page_id = $fb_page_id;
        $channel_facebook->ad_account = $fb_ad_account;
        $channel_facebook->channel_id = $channel->id;
        $channel_facebook->type = $type;

        if($type->is(ChannelFacebookTypeEnum::CHILD_BM())) {
            $facebook_child_bm = new FacebookChildBMService;
            $create_child_bm =  $facebook_child_bm->createChildBM(
                $fb_name,
                $user_access_token,
                $fb_page_id,
                $fb_user_id,
                $fb_vertical,
                $fb_timezone,
            );
    
            if(isset($create_child_bm['error'])) {
                return $create_child_bm;
            }
    
            $channel_facebook->vertical = $fb_vertical;
            $channel_facebook->timezone = $fb_timezone;
            $channel_facebook->page_permitted_tasks = ["ADVERTISE", "ANALYZE", "MANAGE"];
            $channel_facebook->access_token = $create_child_bm['access_token'];
            $channel_facebook->role = 'EMPLOYEE';
            $channel_facebook->child_business_manager_id = $create_child_bm['child_bm_id'];
            $channel_facebook->payment_method_id = $create_child_bm['payment_method_id'] ?? null;
            $channel_facebook->parent_business_manager_id = Auth::user()->account->facebook_business_manager_id;
        } else {
            $channel_facebook->access_token = $fb_access_token;
        }

        $channel_facebook->save();
        
        return $channel_facebook;
    }

    public function update(
        string $title = null,
        string $fb_ad_account = null,
        string $fb_page_id = null,
        string $fb_access_token = null
    ) {
        $this->channel_facebook->ad_account = $fb_ad_account;
        if($this->channel_facebook->type->is(ChannelFacebookTypeEnum::STANDALONE())) {
            $this->channel_facebook->name = $title;
            $this->channel_facebook->page_id = $fb_page_id;
            $this->channel_facebook->access_token = $fb_access_token;
        }
        $this->channel_facebook->save();

        return $this->channel_facebook;
    }

    public function deleteChannelFacebook(Channel $channel)
    {
        if($this->channel_facebook->type->is(ChannelFacebookTypeEnum::CHILD_BM())) {
            $delete_child_bm = FacebookChildBMService::resolve($channel, true)->deleteChildBM($channel->channelFacebook);
    
            if(isset($delete_child_bm['error']) && $delete_child_bm['error']['code'] != 100) {
                return [
                    'error' => true,
                    'message' => $delete_child_bm['error']['error_user_msg'] ?? $delete_child_bm['error']['message']
                ];
            }
        }
        
        return $channel->channelFacebook->delete();
    }

    // public static function deleteChildId($child_bm)
    // {
    //     return FacebookChildBMService::deleteChildBM($child_bm);
    // }

    public function paymentMethod(Channel $channel)
    {
        return false;
    }

    public static function getBusinessManagers(
        string $business_manager_id = null,
        string $app_secret = null,
        string $access_token = null
    )
    {
        $business_manager_id = $business_manager_id ?? Auth::user()->account->facebook_business_manager_id;
        $app_secret = $app_secret ?? Auth::user()->account->facebook_app_secret;
        $access_token = $access_token ?? Auth::user()->account->facebook_access_token;

        $base = self::getUrl() . $business_manager_id . '/owned_businesses';
        $appSecretProof = hash_hmac('sha256', $access_token, $app_secret);

        $payload = [
            'access_token' => $access_token,
            'appsecret_proof' => $appSecretProof
        ];
        $res = Http::get($base, $payload);

        return $res->json();
    }

    public function getFundingSource()
    {
        return false;
    }

    public function createAdAccount(Channel $channel, string $ad_account_name = null)
    {
        $create_ad_account = FacebookChildBMService::resolve($channel, true)->generateAdAccount($channel, $ad_account_name);

        return $create_ad_account;
        
    }

    
    public function createAdAccountSingle(Channel $channel)
    {
        $create_ad_account = FacebookChildBMService::resolve($channel, true)->generateAdAccount($channel, $channel->channelFacebook->name.' Ad Account', true);
        
        return $create_ad_account;
        
    }
    
    public function updateAdAccount(
        Channel $channel,
        string $ad_account_name,
        string $ad_account_id
    )
    {
        return FacebookChildBMService::resolve($channel)->updateAdAccount(
            $channel->channelFacebook,
            $ad_account_name,
            $ad_account_id
        );
    }

    public static function createAccessToken(string $child_bm_id)
    {   
        $channel_facebook = ChannelFacebook::with('channel')->where('child_business_manager_id', $child_bm_id)->first();
        $access_token = FacebookChildBMService::resolve($channel_facebook->channel)->generateAccessToken($child_bm_id);

        return $access_token;
    }

    public static function getParentBMAdAccounts(string $search = null, string $status)
    {
        $call_type = $status == 'pending' ? 'pending_owned_ad_accounts' : 'owned_ad_accounts';
        $url = self::getUrl().Auth::user()->account->facebook_business_manager_id."/{$call_type}";
        
        $payload = [
            'fields' => 'name,id,account_id,permitted_tasks',
            'access_token' => config('facebook.parent_bm.access_token')
        ];
        $api = Http::get( $url,$payload);
        $apiResponse = $api->json();

        if(!$api->ok()) {
            return [
                'error' => true,
                'message' => $apiResponse['error']['error_user_msg'] ?? $apiResponse['error']['message']
            ];
        }

        $availableAdAccount = [];

        $next = true;

        while ($next == true) {

            foreach ($apiResponse['data'] as $res) {
                
                if($search) {
                    
                    $include = 0;

                    // search thru account name
                    if(strpos(strtolower($res['name']), strtolower($search)) !== false) {
                        $include = 1;
                    }

                    // search thru ad account id
                    if(strpos(strtolower($res['account_id']), strtolower($search)) !== false) {
                        $include = 1;
                    }

                    if($include == 1) {
                        $availableAdAccount[] = $res;
                    }
                    
                } else {
                    $availableAdAccount[] = $res;
                }

            }

            $next = false;

            if(isset($apiResponse['paging']['next'])) {
                $apiResponse = Http::get($apiResponse['paging']['next'])->json();
                $next = true;
            }
            
        }

        return $availableAdAccount;
    }

    public static function getChildBMAdAccounts(Channel $channel, FacebookAdAccountStatusEnum $status = null)
    {
        if($channel->id != 141 && $channel->channelFacebook->type->isNot(ChannelFacebookTypeEnum::STANDALONE())) {
            $ad_accounts = FacebookChildBMService::resolve($channel)->getChildBMAdAccounts($channel->channelFacebook, $status);
        } 

        if($channel->channelFacebook->type->is(ChannelFacebookTypeEnum::STANDALONE())) {
            $ad_accounts = [
                [
                    "name" => $channel->channelFacebook->name." Ad Account",
                    "account_status" => 1,
                    "account_id" => $channel->channelFacebook->ad_account,
                    "id" => "act_{$channel->channelFacebook->ad_account}"
                ]
            ];
        }

        return $ad_accounts;
    }


    public static function getFacebookPages($channel_id, $status)
    {

        switch ($status) {
            case 'pending':
                $call_type = 'pending_client_pages';
                break;
            case 'client':
                $call_type = 'client_pages';
                break;
            default:
                $call_type = 'owned_pages';
                break;
        }
        $url = self::getUrl().Auth::user()->account->facebook_business_manager_id."/{$call_type}";
        
        $payload = [
            'fields' => 'name,id',
            'access_token' => config('facebook.parent_bm.access_token')
        ];
        $api = Http::get( $url,$payload);
        $apiResponse = $api->json();

        if(!$api->ok()) {
            return [
                'error' => true,
                'message' => $apiResponse['error']['error_user_msg'] ?? $apiResponse['error']['message']
            ];
        }
        
        $availablePages = [];

        foreach ($apiResponse['data'] as $res) {
            $page_in = 0;
            $channelFacebook = ChannelFacebook::where('page_id', $res['id']);
            if($channel_id){
                $channelFacebook->where('channel_id', '!=', $channel_id);
            }
            
            if(!$channelFacebook->exists() && $res['id'] != config('facebook.parent_bm.primary_page_id')) {
                $availablePages[] = $res;
                $page_in = 1;
            }
            
            if(Auth::user()->email == 'test@jubileearb.app' && $res['id'] == config('facebook.test_facebook_page_id') && $page_in == 0) {
                $availablePages[] = $res;
            }
        }
        
        return $availablePages;
    }

    public static function updateParentBM()
    {
        $appSecretProof = hash_hmac('sha256', config('facebook.parent_bm.access_token'), config('facebook.parent_bm.app_secret'));

        // set primary page
        $payload = [
            'name' => 'Sunwest Capital, llc',
            'primary_page' => config('facebook.parent_bm.primary_page_id'),
            'access_token' => config('facebook.parent_bm.access_token'),
            'appsecret_proof' => $appSecretProof
        ];
        $api = Http::post(self::getUrl() . Auth::user()->account->facebook_business_manager_id, $payload);

        return $api->json();
    }

    public static function getParentBusinessUsers($type)
    {
        $childBMService = new FacebookChildBMService();
        return $childBMService->getBusinessUsers($type);
    }

    public function getUsersFromChild(Channel $channel)
    {
        return FacebookChildBMService::resolve($channel, true)->getUsersFromChild($channel->channelFacebook);
    }

    public function inviteUsersToChild(
        Channel $channel,
        string $email
    )
    {
        return FacebookChildBMService::resolve($channel, true)->inviteUsersToChild(
            $channel,
            $email
        );
    }

    public function deleteUserFromChild(
        Channel $channel,
        string $user_id
    )
    {
        return FacebookChildBMService::resolve($channel)->deleteUserFromChild(
            $channel->channelFacebook,
            $user_id
        );
    }

    public function getPagesFromChild(
        Channel $channel
    )
    {
        return FacebookChildBMService::resolve($channel)->getPagesFromChild(
            $channel->channelFacebook
        );
    }

    public function addPageToChild(Channel $channel, string $page_id )
    {
        return FacebookChildBMService::resolve($channel)->addPageToChild(
            $channel->channelFacebook, 
            $page_id
        );
    }

    public function deletePageFromChild(Channel $channel, string $page)
    {
        return FacebookChildBMService::resolve($channel)->deletePageFromChild(
            $channel->channelFacebook,
            $page
        );
    }
    
    public function assignUsersToPage(Channel $channel, string $user_id)
    {
        return FacebookChildBMService::resolve($channel)->assignUsersToPage(
            $channel->channelFacebook,
            $user_id
        );
    }

    public function generateLocForChild(Channel $channel)
    {
        if(!$channel->channelFacebook->payment_method_id) {
            $response = FacebookChildBMService::resolve($channel)->generateLoc(
                $channel->channelFacebook->child_business_manager_id,
                config('facebook.parent_bm.line_of_credit')
            );

            $channel->channelFacebook->payment_method_id = $response['id'] ?? null;
            $channel->channelFacebook->update();
        }

        return ['payment_method_id' => $channel->channelFacebook->payment_method_id];
    }

    public function getLoc(Channel $channel)
    {
        return FacebookChildBMService::resolve($channel)->getLoc($channel->channelFacebook);
    }

}
