<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\Gmail;
use App\Models\Services\ProgrammaticReportService;

/**
 * Class ProgrammaticService.
 */
class ProgrammaticService
{
    /**
     * @var Account $account
     */
    private $account;

    public static function resolve(Account $account): ProgrammaticService
    {
        $programmaticService = app(self::class);
        $programmaticService->account = $account;

        return $programmaticService;
    }

    public function getAnalyticsReport(): string
    {
        Log::info('start gmail fetch - ' . date('Y-m-d'));

        $client = $this->getClient();
        $service = new Gmail($client);

        $optParams = [];
        $optParams['maxResults'] = 5; // Return Only 5 Messages
        $optParams['labelIds'] = 'INBOX'; // Only show messages in Inbox
        $optParams['q'] = 'Disrupt Analytics Email Report'; // Only show messages in Inbox
        $messages = $service->users_messages->listUsersMessages('me',$optParams);
        $list = $messages->getMessages();
        $messageId = $list[0]->getId(); // Grab first Message

        $optParamsGet = [];
        $optParamsGet['format'] = 'full'; // Display message in payload
        $message = $service->users_messages->get('me',$messageId,$optParamsGet);
        $parts = $message->getPayload()->getParts();

        $body = $parts[1]['body'];
        $filename = $parts[1]['filename'];
        $attachmentId = $body->attachmentId;

        if ($attachmentId == null)
            return "success";

        $attachment = $service->users_messages_attachments->get('me',$messageId,$attachmentId);
        $attachmentData = $attachment->getData();
        $sanitizedData = strtr($attachmentData,'-_', '+/');
        $decodedData = base64_decode($sanitizedData);

        $str = preg_replace('~(,(?=[^"]*"(?:[^"]*"[^"]*")*[^"]*$)|")~', '', $decodedData);
        $row = explode("\n", $str);

        for ($i=1; $i<count($row); $i++) {
            if (!empty(trim($row[$i]))) {
                $data = explode(",", $row[$i]);

                $revenue = str_replace('$', '', $data[16]);

                $reportData = [
                    'campaign_date' => $data[1].' '.$data[2].':00',
                    'domain' => $data[3],
                    'device_category' => $data[4],
                    'country' => $data[5],
                    'utm_adset' => $data[6],
                    'utm_campaign' => $data[7],
                    'utm_content' => $data[8],
                    'utm_medium' => $data[9],
                    'utm_referrer' => $data[10],
                    'utm_source' => $data[11],
                    'utm_subid' => $data[12],
                    'utm_template' => $data[13],
                    'utm_term' => $data[14],
                    'ad_impressions' => $data[15],
                    'estimated_revenue' => $revenue,
                    'pageviews' => $data[17],
                    'sessions' => $data[18]
                ];

                ProgrammaticReportService::create($this->account, (array) $reportData);
            }
        }

        Log::info('done gmail fetch - ' . date('Y-m-d'));

        return 'Success';
    }

    private function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Gmail::GMAIL_READONLY);
        $client->setAuthConfig(\storage_path('app/credentials.json'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $tokenPath = \storage_path('app/token1.json');
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
}
