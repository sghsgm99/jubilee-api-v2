<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\ChannelFacebook;
use App\Models\Enums\FacebookAdAccountStatusEnum;
use App\Models\Enums\FacebookTimezoneEnum;
use App\Models\Enums\FacebookVerticalEnum;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\CampaignFields;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FacebookChildBMService extends FacebookService
{

    public function createChildBM(
        string $fb_name,
        string $user_access_token,
        string $fb_page_id,
        string $fb_user_id,
        FacebookVerticalEnum $fb_vertical,
        FacebookTimezoneEnum $fb_timezone
    )
    {

        // check if user is part of the parent business manager
        // $check_user = $this->getBusinessUsers('pending', $fb_user_id);
        // if(!$check_user) {
        //     $check_user = $this->getBusinessUsers('user', $fb_user_id);
        //     if(!$check_user) {
        //         $invite_user = $this->inviteFacebookUser($fb_user_id);
        //         if(isset($invite_user['success'])) {
        //             return [
        //                 'error' => true,
        //                 'message' => 'Facebook business invitation has been send. Please verfivy in order to proceed.'
        //             ];
        //         }
        //         return [
        //             'error' => true,
        //             'message' => $invite_user->getMessage()
        //         ];
        //     }
        // } else {
        //     return [
        //         'error' => true,
        //         'message' => 'Please verify invitation.'
        //     ];
        // }

        // user is verified and ready to create child BM

        $cbm = [];
        
        $base = self::BASE_URL.config('facebook.version').'/';
        $appSecretProofCBM = hash_hmac('sha256', $user_access_token, $this->app_secret);

        $payload = [
            'name' => $fb_name,
            'primary_page' => $fb_page_id,
            'vertical' => $fb_vertical,
            'page_permitted_tasks' => json_encode(["ADVERTISE", "ANALYZE", "MANAGE"]),
            'timezone_id' => $fb_timezone,
            'access_token' => $user_access_token,
            'appsecret_proof' => $appSecretProofCBM
        ];

        // create CBM API call
        $api = Http::post($base . $this->parent_business_manager_id . '/owned_businesses', $payload);
        $apiCBM = $api->json();
        

        if(!$api->ok()) {
            return [
                'error' => true,
                'message' => $apiCBM['error']['error_user_msg'] ?? $apiCBM['error']['message']
            ];
        }

        $childBM = $api->json()['id'];
        $cbm['child_bm_id'] = $childBM;

        $access_token = $this->generateAccessToken($childBM);
        
        if(isset($access_token['error'])) {
            $access_token = [];
        }

        $cbm['access_token'] = $access_token['access_token'] ?? null;
        

        // assign page to child business manager
        $this->getPagesFromChild($childBM, $fb_page_id);

        // assign page to system user
        $this->assignSystemUserToPage(null, $childBM, $fb_page_id);


        return $cbm;
    }

    public function generateAccessToken($childBM)
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);
        
        // create cbm access token
        $url = self::BASE_URL . config('facebook.version') . "/{$childBM}/access_token";
        $payload = [
            'id' => $childBM,
            'app_id' => $this->app_id,
            'scope' => 'business_management,ads_management',
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ];

        $apiAccessToken = Http::post($url, $payload);
        
        if(!$apiAccessToken->ok()) {
            return [
                'error' => true,
                'message' => $apiAccessToken->json()['error']['error_user_msg'] ?? $apiAccessToken->json()['error']['message'],
                'response' => $apiAccessToken->json()
            ];
        }
        return $apiAccessToken->json();
    }

    public function generateAdAccount(Channel $channel, string $ad_account_name = null, bool $is_single = false)
    {

        // assign page to system user
        $this->assignSystemUserToPage($channel->channelFacebook, $channel->channelFacebook->child_business_manager_id, $channel->channelFacebook->page_id);

        if($is_single && $channel->channelFacebook->ad_account) {
            return $channel;
        }

        
        if(!$channel->channelFacebook->ad_account || $ad_account_name != null) {
            
            // CREATE AD ACCOUNT ENDPOINT
            $parent_bm = $this->parent_business_manager_id;
            $url = self::BASE_URL . config('facebook.version')."/{$channel->channelFacebook->child_business_manager_id}/adaccount";
            $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);
    
            $payload = [
                'name' => $ad_account_name,
                'currency' => 'USD',
                'timezone_id' => $channel->channelFacebook->timezone->value,
                'end_advertiser' => $parent_bm,
                'permitted_tasks' => json_encode(['MANAGE', 'ADVERTISE', 'ANALYZE', 'FB_EMPLOYEE_DSO_ADVERTISE', 'CREATIVE', 'DRAFT']),
                'media_agency' => 'UNFOUND',
                'partner' => 'UNFOUND',
                'funding_id' =>  $channel->channelFacebook->payment_method_id,
                'access_token' => $this->parent_access_token,
                'appsecret_proof' => $appSecretProof
            ];
            
            $response = Http::post($url, $payload);
            $responseData = $response->json();
    
            if (!$response->ok()) {
                return [
                    'error' => true,
                    'message' => $responseData['error']['error_user_msg'] ?? $responseData['error']['message'],
                    'response' => $responseData
                ];
            }
    
            $channel->channelFacebook->ad_account = $responseData['account_id'];
            $channel->channelFacebook->update();       
        }
        
        // assign users to ad account
        $this->getUsersFromChild($channel->channelFacebook);
        
        // assign system user to ad account
        return $this->assignSystemUserToAdAccount($channel, $channel->channelFacebook->ad_account);

        

    }

    public function assignSystemUserToPage(
        ChannelFacebook $channel_facebook = null,
        string $child_bm_id,
        string $page_id
    )
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);
        
        // get system user id
        $system_user = Http::get(self::BASE_URL . config('facebook.version')."/{$child_bm_id}/system_users",[
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ]);

        if(isset($system_user->json()['data'][0])) {
            
            $check = null;

            if($channel_facebook) {
                // check if system user is assigned
                $check = $this->userAssignment($channel_facebook, $system_user->json()['data'][0]['id'], 'assigned_pages', $page_id);
            }

            if(!$channel_facebook || !isset($check['data'][0]) || $check == false) {
                // assign system user
                $url = self::BASE_URL . config('facebook.version')."/{$page_id}/assigned_users";
                $payload = [
                    'user' => $system_user->json()['data'][0]['id'],
                    'tasks' => json_encode(["ADVERTISE", "ANALYZE"]),
                    'business' => $child_bm_id,
                    'access_token' => $this->parent_access_token,
                    'appsecret_proof' => $appSecretProof
                ];
                
                $res = Http::post($url, $payload);
    
                if($res->ok()) {
                    return $res->json();
                }
    
                return [
                    'error' => true,
                    'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                    'response' => $res->json()
                ];
            }

            return $check;

        }

        return [
            'error' => true,
            'message' => 'No system user found'
        ];

    }

    public function assignSystemUserToAdAccount(Channel $channel, string $ad_account)
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);
        
        // get system user id
        $system_user = Http::get(self::BASE_URL . config('facebook.version')."/{$channel->channelFacebook->child_business_manager_id}/system_users",[
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ]);

        if(isset($system_user->json()['data'][0])) {
            $url = self::BASE_URL . config('facebook.version')."/act_{$ad_account}/assigned_users";
            $payload = [
                'user' => $system_user->json()['data'][0]['id'],
                'tasks' => json_encode(["ADVERTISE", "ANALYZE", "MANAGE"]),
                'business' => $channel->channelFacebook->child_business_manager_id,
                'access_token' => $this->parent_access_token,
                'appsecret_proof' => $appSecretProof
            ];
            
            $res = Http::post($url, $payload);

            if($res->ok()) {
                return $channel;
            }

            return [
                'error' => true,
                'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message']
            ];

        }

        return [
            'error' => true,
            'message' => 'No system user found'
        ];

        
    }

    public function deleteChildBM(ChannelFacebook $channel_facebook)
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);
        
        // check for facebook users and remove them in the child bm
        $child_bm_users = $this->getUsersFromChild($channel_facebook);
        foreach ($child_bm_users as $user) {
            $this->deleteUserFromChild($channel_facebook, $user['id']);
        }
        
        // get ad account form parent bm
        $url = self::BASE_URL . config('facebook.version') . '/' . "{$channel_facebook->child_business_manager_id}/owned_ad_accounts";
        $payload = [
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ];

        $ad_account_response = Http::get($url, $payload);
        if($ad_account_response->ok()) {
            // loop ad accounts
            foreach ($ad_account_response->json()['data'] as $ad_account) {
                $act = new AdAccount($ad_account['id']);
                $campaigns = $act->getCampaigns();
                // loop campaigns
                foreach ($campaigns as $campaign) {
                    $campaign->updateSelf([], [CampaignFields::STATUS => 'PAUSED']);
                }
            }
        }

        // delete child bm call
        $base = self::BASE_URL . config('facebook.version') . '/' . $this->parent_business_manager_id . '/owned_businesses';
        $payload = [
            'client_id' => $channel_facebook->child_business_manager_id,
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ];

        $response = Http::delete($base, $payload);
        
        return $response->json();
    }

    public function getChildBMAdAccounts(ChannelFacebook $channel_facebook, FacebookAdAccountStatusEnum $status)
    {
        $url = self::BASE_URL . config('facebook.version') . '/' .$channel_facebook->child_business_manager_id."/owned_ad_accounts";
        
        $payload = [
            'fields' => 'name,id,account_id,permitted_tasks,account_status,funding_source,funding_source_details,invoice',
            'access_token' => $this->parent_access_token
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

                if($status->value) {
                    if($status->value == $res['account_status']) {
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

    public function updateAdAccount(
        ChannelFacebook $channel_facebook,
        string $ad_account_name,
        string $ad_account
    )
    {
            $url = self::BASE_URL . config('facebook.version')."/act_{$ad_account}";
            $payload = [
                'name' => $ad_account_name,
                'access_token' => $channel_facebook->access_token
            ];

            if ($channel_facebook->payment_method_id) {
                $payload['funding_id'] = $channel_facebook->payment_method_id;
                $payload['invoice'] = true;
            } else {
                $payload['invoice'] = true;
            }

            $res = Http::post($url, $payload);

            if (!$res->ok()) {
                return [
                    'error' => true,
                    'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                    'response' => $res->json()
                ];
            }

            return $res->json();
    }

    public function generateLoc(string $child_business_manager_id, string $payment_method_id)
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);

        $base = self::BASE_URL . config('facebook.version') . "/{$payment_method_id}/owning_credit_allocation_configs";
        
        $payload = [
            'receiving_business_id' => $child_business_manager_id,
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ];

        $response = Http::post($base, $payload);

        if (!$response->ok()) {
            return [
                'error' => true,
                'message' => $response->json()['error']['error_user_msg'] ?? $response->json()['error']['message'],
                'response' => $response->json()
            ];
        }

        return $response->json();

    }

    public function getLoc(ChannelFacebook $channel_facebook)
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);

        $base = self::BASE_URL . config('facebook.version') . "/{$channel_facebook->child_business_manager_id}/extendedcredits";

        
        $payload = [
            'fields' => 'id,max_balance',
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ];

        $response = Http::get($base, $payload);

        if (!$response->ok()) {
            return [
                'error' => true,
                'message' => $response->json()['error']['error_user_msg'] ?? $response->json()['error']['message'],
                'response' => $response->json()
            ];
        }

        return $response->json();
    }

    public function getBusinessUsers($type = 'users', $facebook_user_id = null)
    {
        switch ($type) {
            case 'users':
                $url_type = 'business_users';
                break;
                
            case 'pending':
                $url_type = 'pending_users';
                break;
                    
        }
        $url = self::BASE_URL . config('facebook.version') . '/' . $this->parent_business_manager_id . "/{$url_type}";
        
        
        $res = Http::get($url, [
            'access_token' => $this->parent_access_token,
            'app_id' => $this->app_id
        ]);
        
        if(!$res->ok() || !$facebook_user_id) {
            return $res->json();
        }

        foreach ($res->json()['data'] as $user) {
            if($facebook_user_id == $user['id']) {
                return true;    
            }
        }

        return false;

    }

    public function userAssignment(
        ChannelFacebook $channel_facebook,
        string $user_id,
        string $assign = null,
        string $find = null
    )
    {
        $assignment = !$assign ? 'assigned_ad_accounts' : $assign;
        $response = null;
        
        switch ($assignment) {
            case 'assigned_ad_accounts':
                $url = self::BASE_URL . config('facebook.version') . "/{$user_id}/{$assignment}";
                $res = Http::get($url, [
                    'access_token' => $this->parent_access_token
                ]);
        
                if(!$res->ok()) {
                    return [
                        'error' => true,
                        'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                        'content' => $res->json()
                    ];
                }
        
                $response = $res->json();

                break;
            case 'assigned_pages':
                $url = self::BASE_URL . config('facebook.version') . "/{$user_id}/{$assignment}";
                $res = Http::get($url, [
                    'access_token' => $this->parent_access_token
                ]);
        
                if(!$res->ok()) {
                    return [
                        'error' => true,
                        'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                        'content' => $res->json()
                    ];
                }
        
                $response = $res->json();

                break;
        }

        if($find) {
            foreach ($response['data'] as $value) {
                if($value['id'] == $find) {
                    return true;
                }
            }
            return false;
        }

        return $response;

    }

    public function inviteFacebookUser($facebook_user_id)
    {
        $url = self::BASE_URL . config('facebook.version') . '/' . $this->parent_business_manager_id . "/business_users";

        $res = Http::get($url, [
            'email' => $facebook_user_id, // convert facebook user id into email
            'role' => 'EMPLOYEE',
            'access_token' => $this->parent_access_token,
            // 'app_id' => $this->app_id
        ]);

        if(!$res->ok()) {
            return $res->json();
        }
        return ['success' => true];
    }

    public function getUsersFromChild(ChannelFacebook $channel_facebook)
    {
        $users = [];
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);

        // get ad accounts from child bm
        $child_bm_ad_accounts = $this->getChildBMAdAccounts($channel_facebook, FacebookAdAccountStatusEnum::memberByKey('ACTIVE'));
        
        // get pages from child bm
        $child_bm_pages = $this->getPagesFromChild(
            $channel_facebook,
            false,
            true
        );

        foreach (['business_users', 'pending_users'] as $type) {
            $url = self::BASE_URL . config('facebook.version') . '/' . $channel_facebook->child_business_manager_id . "/{$type}";
            $res = Http::get($url, [
                'access_token' => $this->parent_access_token,
                'app_id' => $this->app_id
            ]);
            
            if($res->ok()) {

                $result = $res->json()['data'];

                if($type == 'business_users') {

                    foreach ($result as $key => $user) {
                        
                        // check user if is assigned to ad account
                        $user_assigned_ad_accounts = $this->userAssignment($channel_facebook, $user['id'], 'assigned_ad_accounts');
                        $user_assigned_ad_accounts = $user_assigned_ad_accounts['data'] ?? [];

                        if(isset($child_bm_ad_accounts[0])) {
                            foreach ($child_bm_ad_accounts as $ad_account) {
                                $added = 0;
                                foreach ($user_assigned_ad_accounts as $assigned_account) {
                                    if($assigned_account['id'] == $ad_account['id']) {
                                        $added = 1;
                                    }
                                }
    
                                if($added == 0) {
                                    // add user to ad account
                                    $this->assignUserToAdAccount($channel_facebook, $user['id']);
                                }
                                
                            }
                            $result[$key]['has_ad_account'] = true;
                        } else {
                            $result[$key]['has_ad_account'] = false;
                        }

                        $user_assigned_pages = $this->userAssignment($channel_facebook, $user['id'], 'assigned_pages');

                        foreach ($child_bm_pages as $page) {
                            $added = 0;
                            foreach ($user_assigned_pages['data'] as $user_page) {
                                if($user_page['id'] == $page['id']) {
                                    $added == 1;
                                }
                            }
                            
                            if($added == 0) {
                                // assign users to page
                                $this->assignUsersToPage(
                                    $channel_facebook->child_business_manager_id,
                                    $page['id'],
                                    $user['id']
                                );
                            }
                        }

                    }
                    
                }

                $users = array_merge($users, $result);
            }
        }


        return $users;
    }

    public function inviteUsersToChild(
        $channel,
        $email
    )
    {

        $url = self::BASE_URL . config('facebook.version') . '/' . $channel->channelFacebook->child_business_manager_id;
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);

        $res = Http::post($url."/business_users", [
            'email' => $email,
            'role' => 'EMPLOYEE',
            'tasks' => json_encode(["ADVERTISE", "ANALYZE", "MANAGE"]),
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ]);

        if(!$res->ok()) {
            return [
                'error' => true,
                'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                'content' => $res->json()
            ];
        }

        return $res->json();
    }

    public function deleteUserFromChild(ChannelFacebook $channel_facebook, string $user_id)
    {
        // remove user form ad account
        $res = Http::delete(self::BASE_URL . config('facebook.version') . '/' . $this->act_ad_account_id . '/assigned_users', [
            'user' => $user_id,
            'access_token' => $this->parent_access_token,
            'business' => $channel_facebook->child_business_manager_id,

        ]);
        
        // remove user from facebook page
        $res = Http::delete(self::BASE_URL . config('facebook.version') . '/' . $channel_facebook->page_id . '/assigned_users', [
            'user' => $user_id,
            'access_token' => $this->parent_access_token,
            'business' => $channel_facebook->child_business_manager_id,

        ]);

        // remove user from child bm
        $res = Http::delete(self::BASE_URL . config('facebook.version') . '/' . $user_id, [
            'access_token' => $this->parent_access_token
        ]);

        if(!$res->ok()) {
            return [
                'error' => true,
                'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                'content' => $res->json()
            ];
        }

        return $res->json();
    }

    public function getPagesFromChild(
        ChannelFacebook $channel_facebook,
        bool $with_pending = true,
        bool $forceNoAdd = false
    )
    {
        $availablePages = [];

        foreach (['client_pages', 'pending_client_pages'] as $value) {
            
            if(!$with_pending && $value == 'pending_client_pages') {
                break;
            }
            
            $res = Http::get(self::BASE_URL . config('facebook.version') . '/' . $channel_facebook->child_business_manager_id . "/{$value}", [
                'access_token' => $this->parent_access_token
            ]);
    
            if(!$res->ok()) {
                return [
                    'error' => true,
                    'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                    'content' => $res->json()
                ];
            }
    
            $apiResponse = $res->json();
    
            $next = true;
    
            while ($next == true) {
    
                foreach ($apiResponse['data'] as $res) {
                    $availablePages[] = [
                        'id' => $res['id'],
                        'name' => $res['name'],
                        'status' => $value == 'client_pages' ? 'CONFIRMED' : 'PENDING',
                    ];
                }
    
                $next = false;
    
                if(isset($apiResponse['paging']['next'])) {
                    $apiResponse = Http::get($apiResponse['paging']['next'])->json();
                    $next = true;
                }
                
            }
        }

        if(count($availablePages) < 1 && !$forceNoAdd) {
            // add page to child bm
            $res = $this->addPageToChild(
                $channel_facebook->child_business_manager_id,
                $channel_facebook->page_id,
                false
            );
            
            if(!$res->ok()) {
                return [
                    'error' => true,
                    'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                    'content' => $res->json()
                ];
            }

            return $this->getPagesFromChild($channel_facebook);

        }

        return $availablePages;


    }

    public function addPageToChild(
        ChannelFacebook $channel_facebook,
        string $page_id,
        bool $is_json = true
    )
    {
        $res = Http::post(self::BASE_URL . config('facebook.version') . '/' . $channel_facebook->child_business_manager_id . '/client_pages', [
            'access_token' => $this->parent_access_token,
            'page_id' => $page_id,
            'permitted_tasks' => json_encode(["ADVERTISE", "ANALYZE"])
        ]);


        if(!$res->ok()) {
            return [
                'error' => true,
                'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                'content' => $res->json()
            ];
        }

        // assign page to system user
        $this->assignSystemUserToPage($channel_facebook, $channel_facebook->child_business_manager_id, $page_id);

        if($is_json == false) {
            return $res;
        }

        return $res->json();
    }

    public function deletePageFromChild(
        ChannelFacebook $channel_facebook,
        string $page_id
    )
    {
        $res = Http::delete(self::BASE_URL . config('facebook.version') . '/' . $channel_facebook->child_business_manager_id . '/pages', [
            'access_token' => $this->parent_access_token,
            'page_id' => $page_id
        ]);


        if(!$res->ok()) {
            return [
                'error' => true,
                'message' => $res->json()['error']['error_user_msg'] ?? $res->json()['error']['message'],
                'content' => $res->json()
            ];
        }

        return $res->json();
    }

    public function assignUsersToPage(
        string $child_business_manager_id,
        string $page_id,
        $user_id
    )
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);
        
        // check user if has page access
        $res_user_page = Http::post(self::BASE_URL . config('facebook.version') . "/{$page_id}/assigned_users",[
            'user' => $user_id,
            'tasks' => json_encode(["ADVERTISE", "ANALYZE"]),
            'business' => $child_business_manager_id,
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ]);

        return $res_user_page->json();
    }

    public function assignUserToAdAccount(ChannelFacebook $channel_facebook, $user_id)
    {
        $appSecretProof = hash_hmac('sha256', $this->parent_access_token, $this->app_secret);

        Http::post(self::BASE_URL . config('facebook.version') . "/act_{$channel_facebook->ad_account}/assigned_users",[
            'user' => $user_id,
            'tasks' => json_encode(["ADVERTISE", "ANALYZE", "MANAGE"]),
            'business' => $channel_facebook->child_business_manager_id,
            'access_token' => $this->parent_access_token,
            'appsecret_proof' => $appSecretProof
        ]);
    }

}



