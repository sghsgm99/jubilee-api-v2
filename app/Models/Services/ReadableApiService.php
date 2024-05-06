<?php

namespace App\Models\Services;

use GuzzleHttp\Client;

class ReadableApiService extends ModelService
{
    public function fetchReadableApiData($request)
    {
        $client = new Client();
        $options = [
            'headers' => [
                'API_SIGNATURE' => $request['signature'],
                'API_REQUEST_TIME' => $request['time']
            ],
            'form_params' => [
                'text' => $request['content']
            ]
        ];

        $res = $client->request('POST', $request['url'], $options);

        return $res;
    }
}
