<?php

namespace App\Models\Services;

use GuzzleHttp\Client;

class CopyscapeService extends ModelService
{
    public function fetchCopyscapeBalance($request)
    {
        $client = new Client();
        $operation = "balance";
        $url = $request['url'] .
            '?u=' . urlencode($request['username']) .
            '&k=' . urlencode($request['token']) .
            '&o=' . urlencode($operation) .
            '&f=json';

        $res = $client->request('GET', $url);

        return $res;
    }

    public function fetchTextSearchRequest($request)
    {
        $client = new Client();
        $url = $request['url'] .
            '?u=' . urlencode($request['username']) .
            '&k=' . urlencode($request['token']) .
            '&o=' . urlencode($request['operation']) .
            '&e=UTF-8' .
            '&c=5' .
            '&f=json' .
            '&x=' . urlencode($request['activate_test']);

        $payload = [
            'form_params' => [
                't' => $request['content']
            ]
        ];

        $res = $client->request('POST', $url, $payload);

        return $res;
    }
}
