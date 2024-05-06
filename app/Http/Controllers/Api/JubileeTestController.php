<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Faker\Guesser\Name;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use DateTime;
use FacebookAds\Api;
use FacebookAds\Object\AdUser;
use FacebookAds\Object\Fields\AdUserFields;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\Values\AdsInsightsDatePresetValues;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Logger\CurlLogger;
//use FacebookAds\Object\User;
use FacebookAds\Object\Page;
use FacebookAds\Object\PagePost;
use FacebookAds\Object\TargetingSearch;
use FacebookAds\Object\Search\TargetingSearchTypes;
use FacebookAds\Object\Targeting;
use FacebookAds\Object\Fields\TargetingFields;
use FacebookAds\Object\Values\AdSetOptimizationGoalValues;
use FacebookAds\Object\Values\AdSetBillingEventValues;
use FacebookAds\Object\Values\AdSetStatusValues;

use App\Services\GoogleCampaignService;
use App\Services\GoogleAdGroupService;
use App\Services\GoogleAdService;
use App\Jobs\ProcessFacebookCampaign;
use App\Jobs\ProcessTestJob;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\FacebookRuleAutomation;
use App\Services\TaboolaService;
use App\Services\YahooService;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReportImport;
use App\Models\Account;
use App\Services\ClickscoService;
use App\Models\GoogleCampaign;
use App\Models\GoogleAdgroup;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;
use GuzzleHttp\Client;
use App\Models\Enums\SiteMenuTypeEnum;
use App\Services\ResponseService;
use App\Services\StableDiffusionAIService;
use App\Services\OpenAIService;
use App\Services\GoogleAICampaignService;
use App\Models\Services\GoogleImageModelService;
use App\Models\GoogleAICampaign;

use App\Services\FileService;
use App\Models\Enums\StorageDiskEnum;
use App\Services\GmailReportService;

class JubileeTestController extends Controller
{
    public function test(Request $request)
    {
        $account = Account::query()->where('id', 1)->first();
        return GmailReportService::resolve($account)->getAdsenseReport();


        $gac = GoogleAICampaign::find(9);
        $googleAICampaignService = GoogleAICampaignService::resolve(1);

        return $googleAICampaignService->test($gac);

        /*$gac->campaign_id ='21152854928';
        $gac->adgroup_id = '158423788497';
        $gac->status = 3;
        $gac->errored_at = null;
        $gac->error_message = null;
        $gac->save();

        $adid = $googleAICampaignService->createSearchAd($gac, $gac->adgroup_id);
        $gac->ad_id = $adid;
        $gac->save();
        return 1;*/
        
        //return $gac->Service()->processCampaign();


        /*$title = "Biolyfe Keto Gummies";
        $prompt = $title." - generate 3 headlines without special characters, limit 20 characters";
        $aitext = OpenAIService::resolve()->generateAIText($prompt);

        return $aitext;*/

        /*$aitext="Keep drinks cold longer\n Sleek, durable design\n Perfect for on-the-go";
        $lines = explode("\n", $aitext);

        $numberedItems = [];

        // Iterate through each line
        foreach ($lines as $line) {
            // Use regular expression to match the numbered items
            preg_match('/^\d+\.\s*(.*)$/', $line, $matches);
            
            // If a match is found, add the item to the numberedItems array
            if (!empty($matches)) {
                $numberedItems[] = $matches[1];
            }
        }

        return $numberedItems;*/

      //'https://jubilee-api-bucket.sfo3.digitaloceanspaces.com/google/images/image19.jpg',
      //'https://jubilee-api-bucket.sfo3.digitaloceanspaces.com/google/images/image20.jpg',
            
      //$image_url = "https://cdn2.stablediffusionapi.com/generations/0-e1fcb7d2-0c84-4eac-b50c-0d85d67b680e.png";


      //return StableDiffusionAIService::resolve()->generateAIImageEx("bmw car", 1, 512, 256);

      /*$title = "Register For Hemophilia Therapy Free Trial";
      $stablediffusionAIService = StableDiffusionAIService::resolve();
      $image_urls = $stablediffusionAIService->generateAIImageEx($title, 1, 512, 512);
      sleep(5);
      $file_name = pathinfo($image_urls[0], PATHINFO_FILENAME);
      $fs = FileService::main(StorageDiskEnum::PUBLIC_DO(), 'stable-diffusion');
      $marketing_img = $fs->uploadResizeImage($image_urls[0], $file_name.'-m', 600, 314);
      $square_marketing_image = $fs->uploadResizeImage($image_urls[0], $file_name.'-sm', 300, 300);

      return [
        'm' => $marketing_img,
        'sm' => $square_marketing_image
      ];*/


      


      /*$url = "https://xml-nar-ss.ysm.yahoo.com/d/search/p/disrupt/xmlb/multi/?Keywords=shoes&mkt=us&Partner=dissoc_n2s_xmlb_11249_fb_sociallysearching&serveUrl=https%3A%2F%2Fsociallysearching.com%2Fsearch%2Ftop5%2F&affilData=ip%3D54.205.105.183%26ua%3DMozilla%2F5.0%20%28Windows%20NT%206.3%3B%20Win64%3B%20x64%3B%20rv%3A109.0%29%20Gecko%2F20100101%20Firefox%2F115.0";
      $xml = file_get_contents($url, false);
      $xmls = simplexml_load_string($xml);
      $json = json_encode($xmls);
      $response = json_decode($json);
      return $response;*/

      /*$openAIService = OpenAIService::resolve();
      $prompt = "tesla car - generate 5 headlines";
      $text = $openAIService->generateAIText($prompt);

      $vals = explode("\n", $text);

      foreach ($vals as $description) {
        echo $description;
        echo '\n-----------';
      }*/



      //return StableDiffusionAIService::resolve()->generateAIImageEx("bmw car", 1, 608, 320);

      $payload = [
        
        "key"=> "x3h3ueizpslehr",
        "model_id"=> "sdxl-unstable-diffus",
        "prompt"=> "tesla car",
        "width"=> "512",
        "height"=> "512",
        "samples"=> "1",
        "num_inference_steps"=> "30",
        "seed"=> null,
        "guidance_scale"=> 7.5,
        "webhook"=> null,
        "track_id"=> null
      ];

      $client = new Client();

      $response = $client->post('https://stablediffusionapi.com/api/v1/enterprise/text2img', [
          'headers' => [
              'Content-Type' => 'application/json'
          ],
          'json' => $payload,
      ]);
      $result = json_decode($response->getBody()->getContents(), true);

      return $result;

      return "Welcome jubilee api test";
    }

    public function createAd1()
    {
        $customerId = '3170615631';
        $adGroupId = '165478469448';
        $status = CampaignStatus::PAUSED;
        $title = "Zero Cost Hemophilia Drug Test";
    
        $adGroupResourceName = ResourceNames::forAdGroup($customerId, $adGroupId);

        $openAIService = OpenAIService::resolve();

        $prompt = $title." - generate headline, limit 20 characters";
        $text = $openAIService->generateAIText($prompt);
        $headlines[] = self::createAdTextAsset($text);
        //Log::info('headline - ' . $text);
        
        $prompt = $title." - generate description, limit 80 characters";
        $text = $openAIService->generateAIText($prompt);
        $descriptions[] = self::createAdTextAsset($text);
        
        $stablediffusionAIService = StableDiffusionAIService::resolve();
        $image_urls = $stablediffusionAIService->generateAIImageEx($title, 1, 512, 512);
        sleep(5);
        $file_name = pathinfo($image_urls[0], PATHINFO_FILENAME);
        $fs = FileService::main(StorageDiskEnum::PUBLIC_DO(), 'stable-diffusion');
        $marketing_img = $fs->uploadResizeImage($image_urls[0], $file_name.'-m', 600, 314);
        $square_marketing_img = $fs->uploadResizeImage($image_urls[0], $file_name.'-sm', 300, 300);

        $marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $marketing_img
        );
        
        $square_marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $square_marketing_img
        );

        $prompt = $title." - generate long headline, limit 80 characters";
        $long_headline = $openAIService->generateAIText($prompt);

        $prompt = $title." - generate business name, limit 20 characters";
        $business_name = $openAIService->generateAIText($prompt);

        $dataInfo = [
            'marketing_images' => $marketing_images,
            'square_marketing_images' => $square_marketing_images,
            'headlines' => $headlines,
            'long_headline' => new AdTextAsset(['text' => $long_headline]),
            'descriptions' => $descriptions,
            'business_name' => $business_name
        ];

        // Creates the responsive display ad info object.
        $responsiveDisplayAdInfo = new ResponsiveDisplayAdInfo($dataInfo);

        // Creates a new ad group ad.
        $adGroupAd = new AdGroupAd([
            'ad' => new Ad([
                'responsive_display_ad' => $responsiveDisplayAdInfo,
                'final_urls' => ['https://socialsearchtoday.com/pla/1/?s=shoes&t=02shoesFB']
            ]),
            'status' => $status,
            'ad_group' => $adGroupResourceName
        ]);

        // Creates an ad group ad operation.
        $adGroupAdOperation = new AdGroupAdOperation();
        $adGroupAdOperation->setCreate($adGroupAd);

        // Issues a mutate request to add the ad group ad.
        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds($customerId, [$adGroupAdOperation]);

        /** @var AdGroupAd $addedAdGroupAd */
        $addedAdGroupAd = $response->getResults()[0];

        $adId = explode("~", $addedAdGroupAd->getResourceName())[1];
        
        return $adId;
    }
}
