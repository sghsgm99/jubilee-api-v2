<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\ResponseService;

class StableDiffusionAIService
{
    private $client;
    private $endpoint;
    private $api_key = '';
    private $enterprise_endpoint;
    private $enterprise_api_key = '';
    private $enterprise_model = '';
    
    public static function resolve(): StableDiffusionAIService
    {
        $aiService = app(self::class);
        $aiService->client = new Client();
        $aiService->endpoint = 'https://stablediffusionapi.com/api/v3/text2img';
        $aiService->api_key = env('STABLE_DIFFUSSION_API_KEY');

        $aiService->enterprise_endpoint = 'https://stablediffusionapi.com/api/v1/enterprise/text2img';
        $aiService->enterprise_api_key = 'x3h3ueizpslehr';
        $aiService->enterprise_model = 'sdxl-unstable-diffus';
        
        return $aiService;
    }

    public function generateAIImage(
        string $prompt,
        int $count = 1,
        int $width = 512,
        int $height = 512
    )
    {
        $payload = [
            "key" => $this->api_key, 
            "prompt" => $prompt, 
            "width"=> $width,
            "height"=> $height,
            "safety_checker"=> "no",
            "guidance"=> 7.5,
            "instant_response"=> null,
            "samples"=> $count,
            "steps"=> 41,
            "temp"=> null,
            "seed"=> null,
            "webhook"=> null,
            "track_id"=> null
          ];

        try {
            $response = $this->client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload,
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $exception) {
            return ResponseService::clientError('Bad Request', [
                'response' => $exception->getMessage()
            ]);
        }

        if ($result['status'] == 'success')
            return $result['proxy_links'];

        return [];
    }

    //enterprise plan
    public function generateAIImageEx(
        string $prompt,
        int $count = 1,
        int $width = 512,
        int $height = 512
    )
    {
        $payload = [
            "key"=> $this->enterprise_api_key,
            "model_id"=> $this->enterprise_model,
            "prompt"=> $prompt,
            "width"=> $width,
            "height"=> $height,
            "samples"=> $count,
            "num_inference_steps"=> "30",
            "seed"=> null,
            "guidance_scale"=> 7.5,
            "webhook"=> null,
            "track_id"=> null
          ];

        try {
            $response = $this->client->post($this->enterprise_endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload,
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $exception) {
            return ResponseService::clientError('Bad Request', [
                'response' => $exception->getMessage()
            ]);
        }

        if ($result['status'] == 'success')
            return $result['proxy_links'];

        return [];
    }
}
