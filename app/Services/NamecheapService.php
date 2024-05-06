<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Namecheap\Api;
use Namecheap\Domain\Domains;
use Namecheap\Domain\DomainsDns;
use Namecheap\Domain\DomainsNs;
use Stripe\StripeClient;

class NamecheapService
{
    protected $apiUser = '';
    protected $userName = '';
    protected $apiKey = '';
    protected $clientIp = '';

    public function __construct()
    {
        $this->apiUser = $this->userName = config('dns.namecheap.production.api_user');
        $this->apiKey = config('dns.namecheap.production.api_key');
        $this->clientIp = config('dns.namecheap.production.client_ip');
    }

    public function connection($type)
    {
        $returnType = 'json';

        $client = new Api($this->apiUser, $this->apiKey, $this->userName, $this->clientIp, $returnType);
        $client->setCurlOption(CURLOPT_SSL_VERIFYPEER, false); // For local development env (if needed)

        if ($type == 'domains') {
            $ncDomains = new Domains($client);
        } elseif($type == 'dns') {
            $ncDomains = new DomainsDns($client);
        }

        return $ncDomains;
    }

    public function getDomains()
    {
        $ncDomains = $this->connection('domains');
        $domainList = $ncDomains->getList();

        return $domainList;
    }

    public function getHosts($sld, $tld)
    {
        $ncDomains = $this->connection('dns');
        $response = $ncDomains->getHosts($sld, $tld);

        return $response;
    }

    public function addRecord($sld, $tld, $hostName, $recordType, $address)
    {
        $ncDomains = $this->connection('dns');
        $response = $ncDomains->setHosts($sld, $tld, $hostName, $recordType, $address, [], null);

        return $response;
    }

    public function getHostsSandbox($sld, $tld)
    {
        $apiUser1 = $userName1 = config('dns.namecheap.sandbox.api_user');
        $apiKey1 = config('dns.namecheap.sandbox.api_key');
        $clientIp1 = config('dns.namecheap.sandbox.client_ip');

        $returnType = 'json';
        $client = new Api($apiUser1, $apiKey1, $userName1, $clientIp1, $returnType);
        $client->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);

        $ncDomains = new DomainsDns($client);

        $ncDomains->enableSandbox();

        return $ncDomains->getHosts($sld, $tld);
    }
}
