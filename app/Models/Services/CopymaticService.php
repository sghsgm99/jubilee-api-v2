<?php

namespace App\Models\Services;

use GuzzleHttp\Client;

class CopymaticService extends ModelService
{
    public function fetchCopymaticData($request)
    {
        $client = new Client();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $request['token'],
            ],
            'body' => json_encode([
                'model' => $request['model'],
                'tone' => $request['tone'],
                'language' => $request['language'],
                'creativity' => $request['creativity'],
                'content' => $request['content']
            ])
        ];

        $res = $client->request('POST', $request['url'], $options);

        return $res;
    }
}
