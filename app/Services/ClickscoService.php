<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Gmail;
use App\Models\Services\ClickscoReportService;

class ClickscoService
{
    /**
     * @var Account $account
     */
    private $account;

    public static function resolve(Account $account): ClickscoService
    {
        $clickscoService = app(self::class);
        $clickscoService->account = $account;

        return $clickscoService;
    }

    private function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Gmail::GMAIL_READONLY);
        $client->setAuthConfig(\storage_path('app/credentials.json'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $tokenPath = \storage_path('app/public/oleg-token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    public function getReportDetail()
    {
        $client = $this->getClient();
        $service = new Gmail($client);

        $optParams = [];
        $optParams['maxResults'] = 5; // Return Only 5 Messages
        $optParams['labelIds'] = 'INBOX'; // Only show messages in Inbox
        $optParams['q'] = 'from:deepak@clicksco.com'; // Only show messages in Inbox
        $messages = $service->users_messages->listUsersMessages('me',$optParams);
        $list = $messages->getMessages();
        $messageId = $list[0]->getId(); // Grab first Message

        $optParamsGet = [];
        $optParamsGet['format'] = 'full'; // Display message in payload
        $message = $service->users_messages->get('me',$messageId,$optParamsGet);
        $parts = $message->getPayload()->getParts();

        $links_data_id = 0;
        $raw_type_id = 0;

        for ($i=0; $i<count($parts); $i++) {
            if ($parts[$i]['filename'] == 'raw_yahoo_types.csv')
                $raw_type_id = $i;

            if ($parts[$i]['filename'] == 'links_data.csv')
                $links_data_id = $i;
        }

        if ($raw_type_id > 0 && $links_data_id > 0) {

            //links_data
            $body = $parts[$links_data_id]['body'];
            $filename = $parts[$links_data_id]['filename'];
            $attachmentId = $body->attachmentId;

            if ($attachmentId == null)
                return "success";

            $attachment = $service->users_messages_attachments->get('me',$messageId,$attachmentId);
            $attachmentData = $attachment->getData();
            $sanitizedData = strtr($attachmentData,'-_', '+/');
            $decodedData = base64_decode($sanitizedData);

            $str = preg_replace('~(,(?=[^"]*"(?:[^"]*"[^"]*")*[^"]*$)|")~', '', $decodedData);
            $row = explode("\n", $str);

            $linkData = [];

            for ($i=1; $i<count($row); $i++) {
                if (!empty(trim($row[$i]))) {
                    $data = explode(",", $row[$i]);

                    $linkData[] = [
                        'partner_id' => $data[1],
                        'link_date' => $data[3],
                        'link_campaign' => $data[4],
                        'link_adgroup' => $data[5],
                        'link_type_uid' => str_replace("\r", '', $data[17])
                    ];
                }
            }

            //yahoo types
            $body = $parts[$raw_type_id]['body'];
            $filename = $parts[$raw_type_id]['filename'];
            $attachmentId = $body->attachmentId;

            /*if ($filename != 'raw_yahoo_types.csv')
                return "nothing report";*/

            if ($attachmentId == null)
                return "success";

            $attachment = $service->users_messages_attachments->get('me',$messageId,$attachmentId);
            $attachmentData = $attachment->getData();
            $sanitizedData = strtr($attachmentData,'-_', '+/');
            $decodedData = base64_decode($sanitizedData);

            $str = preg_replace('~(,(?=[^"]*"(?:[^"]*"[^"]*")*[^"]*$)|")~', '', $decodedData);
            $row = explode("\n", $str);

            $reportData = [];

            for ($i=1; $i<count($row); $i++) {
                if (!empty(trim($row[$i]))) {
                    $data = explode(",", $row[$i]);

                    $ltu = $data[7];

                    $filter = array_filter($linkData, function($v) use ($ltu) {
                        return $v['link_type_uid'] == $ltu;
                    });

                    $campaign_name = "unknown";
                    $adgroup_name = "unknown";
                    $partner_id = 0;

                    if (!empty($filter)) {
                        $campaign_name = reset($filter)["link_campaign"];
                        $adgroup_name = reset($filter)["link_adgroup"];
                        $partner_id = reset($filter)["partner_id"];
                    }

                    $reportData[] = [
                        'ryt_date' => $data[0],
                        'device_type' => $data[3],
                        'link_type_uid' => $ltu,
                        'impression' => $data[9],
                        'bidded_impression' => $data[10],
                        'clicks_sold' => $data[11],
                        'revenue' => $data[12],
                        'campaign_name' => $campaign_name,
                        'adgroup_name' => $adgroup_name,
                        'partner_id' => $partner_id
                    ];
                }
            }

            $tmp = array_reduce($reportData, function($carry, $item){ 
                if(!isset($carry[$item['campaign_name']])){
                    $dd[] = [
                        'date' => $item['ryt_date'],
                        'impression' => $item['impression'],
                        'revenue' => $item['revenue']
                    ];

                    $carry[$item['campaign_name']] = [
                        'campaign_name'=>$item['campaign_name'],
                        'adgroup_name'=>$item['adgroup_name'],
                        'partner_id'=>$item['partner_id'],
                        'impression'=>$item['impression'],
                        'bidded_impression'=>$item['bidded_impression'],
                        'clicks_sold'=>$item['clicks_sold'],
                        'revenue'=>$item['revenue'],
                        'detail' => $dd
                    ];
                } else { 
                    $dd = $carry[$item['campaign_name']]['detail'];
                    $dd[] = [
                        'date' => $item['ryt_date'],
                        'impression' => $item['impression'],
                        'revenue' => $item['revenue']
                    ];

                    $carry[$item['campaign_name']]['impression'] += $item['impression'];
                    $carry[$item['campaign_name']]['bidded_impression'] += $item['bidded_impression'];
                    $carry[$item['campaign_name']]['clicks_sold'] += $item['clicks_sold'];
                    $carry[$item['campaign_name']]['revenue'] += $item['revenue'];
                    $carry[$item['campaign_name']]['detail'] = $dd;
                }

                return $carry; 
            });
        }

        $result = [];
        
        $total = [
            'revenue' => 0,
            'impression' => 0
        ];

        foreach ($tmp as $key => $v) {
            $result[] = [
                'campaign_name' => $v['campaign_name'],
                'adgroup_name' => $v['adgroup_name'],
                'partner_id' => $v['partner_id'],
                'impression' => $v['impression'],
                'bidded_impression' => $v['bidded_impression'],
                'clicks_sold' => $v['clicks_sold'],
                'revenue' => $v['revenue'],
                'detail' => $v['detail']
            ];

            $total['impression'] += $v['impression'];
            $total['revenue'] += $v['revenue'];
        }

        return [
            'data' => $result,
            'grandTotal' => $total
        ];
    }

    public function getReport()
    {
        $client = $this->getClient();
        $service = new Gmail($client);

        $optParams = [];
        $optParams['maxResults'] = 1; // Return Only 5 Messages
        $optParams['labelIds'] = 'INBOX'; // Only show messages in Inbox
        $optParams['q'] = 'from:deepak@clicksco.com'; // Only show messages in Inbox
        $messages = $service->users_messages->listUsersMessages('me',$optParams);
        $list = $messages->getMessages();
        $messageId = $list[0]->getId(); // Grab first Message

        $optParamsGet = [];
        $optParamsGet['format'] = 'full'; // Display message in payload
        $message = $service->users_messages->get('me',$messageId,$optParamsGet);
        $parts = $message->getPayload()->getParts();

        $links_data_id = 0;
        $raw_type_id = 0;

        for ($i=0; $i<count($parts); $i++) {
            if ($parts[$i]['filename'] == 'raw_yahoo_types.csv')
                $raw_type_id = $i;

            if ($parts[$i]['filename'] == 'links_data.csv')
                $links_data_id = $i;
        }

        if ($raw_type_id > 0 && $links_data_id > 0) {

            //links_data
            $body = $parts[$links_data_id]['body'];
            $filename = $parts[$links_data_id]['filename'];
            $attachmentId = $body->attachmentId;

            if ($attachmentId == null)
                return "success";

            $attachment = $service->users_messages_attachments->get('me',$messageId,$attachmentId);
            $attachmentData = $attachment->getData();
            $sanitizedData = strtr($attachmentData,'-_', '+/');
            $decodedData = base64_decode($sanitizedData);

            $str = preg_replace('~(,(?=[^"]*"(?:[^"]*"[^"]*")*[^"]*$)|")~', '', $decodedData);
            $row = explode("\n", $str);

            $linkData = [];

            for ($i=1; $i<count($row); $i++) {
                if (!empty(trim($row[$i]))) {
                    $data = explode(",", $row[$i]);

                    $linkData[] = [
                        'partner_id' => $data[1],
                        'link_date' => $data[3],
                        'link_campaign' => $data[4],
                        'link_adgroup' => $data[5],
                        'link_type_uid' => str_replace("\r", '', $data[17])
                    ];
                }
            }

            //yahoo types
            $body = $parts[$raw_type_id]['body'];
            $filename = $parts[$raw_type_id]['filename'];
            $attachmentId = $body->attachmentId;

            /*if ($filename != 'raw_yahoo_types.csv')
                return "nothing report";*/

            if ($attachmentId == null)
                return "success";

            $attachment = $service->users_messages_attachments->get('me',$messageId,$attachmentId);
            $attachmentData = $attachment->getData();
            $sanitizedData = strtr($attachmentData,'-_', '+/');
            $decodedData = base64_decode($sanitizedData);

            $str = preg_replace('~(,(?=[^"]*"(?:[^"]*"[^"]*")*[^"]*$)|")~', '', $decodedData);
            $row = explode("\n", $str);

            $reportData = [];

            for ($i=1; $i<count($row); $i++) {
                if (!empty(trim($row[$i]))) {
                    $data = explode(",", $row[$i]);

                    $ltu = $data[7];

                    $filter = array_filter($linkData, function($v) use ($ltu) {
                        return $v['link_type_uid'] == $ltu;
                    });

                    $campaign_name = "unknown";
                    $adgroup_name = "unknown";
                    $partner_id = 0;

                    if (!empty($filter)) {
                        $campaign_name = reset($filter)["link_campaign"];
                        $adgroup_name = reset($filter)["link_adgroup"];
                        $partner_id = reset($filter)["partner_id"];
                    }

                    $reportData[] = [
                        'ryt_date' => $data[0],
                        'device_type' => $data[3],
                        'link_type_uid' => $ltu,
                        'impression' => $data[9],
                        'bidded_impression' => $data[10],
                        'clicks_sold' => $data[11],
                        'revenue' => $data[12],
                        'campaign_name' => $campaign_name,
                        'adgroup_name' => $adgroup_name,
                        'partner_id' => $partner_id
                    ];
                }
            }

            $this->storeCSVData($reportData, $message->internalDate);
        }

        Log::info('finish clicksco report - ' . date('Y-m-d'));
        return [
            'success' => true,
            'message' => 'Success'
        ];
    }

    private function storeCSVData($report_data, $report_date)
    {
        DB::beginTransaction();

        try {
            Log::info('start clicksco extract csv file- ' . date('Y-m-d'));

            foreach ($report_data as $row) {
                ClickscoReportService::create(
                    $this->account,
                    "Clicksco Report",
                    Carbon::parse($row['ryt_date']),
                    $row
                );
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('failed clicksco extract csv file: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

}
