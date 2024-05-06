<?php

namespace App\Services;

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsClientBuilder;
use Google\ApiCore\ApiException;
use DateTime;

class GoogleService
{
    protected $googleAdsClient;

    public static function resolve(
        int $google_account
    ) {
        $google_accounts = config('google.account');

        foreach ($google_accounts as $account) {
            if ($account['value'] == $google_account) {
                $config_path = $account['credential'];
                break;
            }
        }

        $config_path = 'app/' . $config_path;

        $self = app(static::class);
        $self->googleAdsClient = (new GoogleAdsClientBuilder())
            ->fromFile(storage_path($config_path))
            ->withOAuth2Credential((new OAuth2TokenBuilder())
                ->fromFile(storage_path($config_path))
                ->build())
            ->build();

        return $self;
    }

    public function getCampaigns($customerId)
    {
        $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();

        $query = 'SELECT campaign.id, campaign.name FROM campaign ORDER BY campaign.id';
        // Issues a search stream request.
        /** @var GoogleAdsServerStreamDecorator $stream */
        $stream =
            $googleAdsServiceClient->searchStream($customerId, $query);

        // Iterates over all rows in all messages and prints the requested field values for
        // the campaign in each row.
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            /** @var GoogleAdsRow $googleAdsRow */
            printf(
                "Campaign with ID %d and name '%s' was found.%s",
                $googleAdsRow->getCampaign()->getId(),
                $googleAdsRow->getCampaign()->getName(),
                PHP_EOL
            );
        }
    }

    public function getPrintableDatetime(): string
    {
        return (new DateTime())->format("Y-m-d\TH:i:s.vP");
    }
}
