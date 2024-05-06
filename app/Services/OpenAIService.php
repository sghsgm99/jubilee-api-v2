<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenAIService
{
    private $client;
    private $txt_endpoint;
    private $img_endpoint;
    private $api_key = '';
    
    public static function resolve(): OpenAIService
    {
        $aiService = app(self::class);
        $aiService->client = new Client();
        $aiService->txt_endpoint = 'https://api.openai.com/v1/chat/completions';
        $aiService->img_endpoint = 'https://api.openai.com/v1/images/generations';
        $aiService->api_key = env('OPENAI_API_KEY');
        
        return $aiService;
    }

    public function generateAIText(
        string $prompt,
        int $max_tokens = 250
    )
    {
        $response = $this->client->post($this->txt_endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'json' => [
                "model" => "gpt-3.5-turbo",
                "max_tokens" => $max_tokens,
                "messages"=> [["role"=> "user", "content"=> $prompt]],
                "temperature" => 0.7
            ],
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        
        return $result['choices'][0]['message']['content'];
    }

    public function generateAIImage(
        string $prompt,
        int $count,
        string $size
    )
    {
        $response = $this->client->post($this->img_endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'json' => [
                "prompt" => $prompt,
                "n" => $count,
                "size" => $size
            ],
        ]);
        $result = json_decode($response->getBody()->getContents(), true);

        return $result['data'];
    }
}
