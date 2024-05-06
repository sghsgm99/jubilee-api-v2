<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateSubdomainRequest;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\SubDomain;
use App\Services\ResponseService;
use App\Http\Resources\SubDomainResource;
use App\Models\Services\SubDomainService;
use App\Http\Requests\CreateSubDomainRequest;
use App\Services\NamecheapService;
use GuzzleHttp\Client;

class SubDomainController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('subdomains', [SubDomainController::class, 'create']);
        Route::get('subdomains', [SubDomainController::class, 'getCollection']);
        Route::put('subdomains/{subdomain}', [SubDomainController::class, 'update']);
        Route::delete('subdomains/{subdomain}', [SubDomainController::class, 'delete']);
        Route::get('subdomains/fetch', [SubDomainController::class, 'getFetchSubDomains']);
        Route::get('subdomains/namecheap/{subdomain}', [SubDomainController::class, 'setHost']);
        Route::get('subdomains/namecheap-sandbox', [SubDomainController::class, 'getSandboxHosts']);
    }

    public function create(CreateSubDomainRequest $request)
    {
        return ResponseService::successCreate(
            'SubDomain was created.',
            new SubDomainResource(
                SubDomainService::create(
                    Domain::findOrFail($request->validated()['domain_id']),
                    $request->validated()['name']
                )
            )
        );
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $subdomains = SubDomain::search($search, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return SubDomainResource::collection($subdomains);
    }

    public function getFetchSubDomains(Request $request)
    {
        $domain_id = $request->input('domain_id', null);
        $sort = $request->input('sort', 'name');
        $sort_type = $request->input('sort_type', 'asc');

        $subdomains = SubDomain::searchId($domain_id, $sort, $sort_type);

        return SubDomainResource::collection($subdomains);
    }

    public function update(UpdateSubdomainRequest $request, SubDomain $subdomain)
    {
        return ResponseService::success(
            'Subdomain was updated',
            new SubDomainResource(
                $subdomain->Service()->update($request->validated()['name'])
            )
        );
    }

    public function delete(SubDomain $subdomain)
    {
        if (!$subdomain->Service()->delete()) {
            return ResponseService::serverError('SubDomain cannot be deleted');
        }

        return ResponseService::success('SubDomain was archived.');
    }

    public function setHost(SubDomain $subdomain)
    {
        $sld = explode('.', $subdomain->domains['domain'])[0];
        $tld = explode('.', $subdomain->domains['domain'])[1];

        $namecheapservice = new NamecheapService;

        $res = $namecheapservice->getHosts($sld, $tld);
        $hosts = json_decode($res);

        if ($hosts->ApiResponse->Errors != "") {
            return ResponseService::serverError($hosts->ApiResponse->Errors->Error->__text);
        }

        $i = 1;
        if (isset($hosts->ApiResponse->CommandResponse->DomainDNSGetHostsResult->host)) {
            foreach($hosts->ApiResponse->CommandResponse->DomainDNSGetHostsResult->host as $host) {
                $hostName['HostName'.$i] = $host->_Name;
                $recordType['RecordType'.$i] = $host->_Type;
                $address['Address'.$i] = $host->_Address;
                $i++;
            }
        }

        $hostName['HostName'.$i] = '@';
        $recordType['RecordType'.$i] = 'A';
        $address['Address'.$i] = $subdomain->domains['ipaddress'];

        $i++;
        $hostName['HostName'.$i] = $subdomain->name;
        $recordType['RecordType'.$i] = 'CNAME';
        $address['Address'.$i] = $subdomain->domains['domain'];

        $res = $namecheapservice->addRecord($sld, $tld, $hostName, $recordType, $address);
        $dnsRecord = json_decode($res);

        if ($dnsRecord->ApiResponse->Errors != "") {
            return ResponseService::serverError('Namecheap Error:', $dnsRecord->ApiResponse->Errors);
        }

        $client = new Client();
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'token ' . config('dns.git.token')
            ],
            'body' => json_encode([
                'ref' => config('dns.git.ref'),
                'inputs' => [
                    "host" => $subdomain->domains['ipaddress'],
                    "servn" => $subdomain->domains['domain'],
                    "cname" => $subdomain->name,
                    "username" => $subdomain->domains['username'],
                    "password" => config('dns.ssh.password')
                ],
            ])
        ];

        try {
            $response = $client->request('POST', config('dns.git.url'), $options);
        } catch (\Throwable $exception) {
            return ResponseService::clientError('Bad Request', [
                'payload' => $options,
                'response' => $exception->getMessage()
            ]);
        }

        $subdomain = $subdomain->Service()->active();

        if (isset($subdomain['error'])) {
            return ResponseService::serverError($subdomain['message']);
        }

        return ResponseService::success('Subdomain Virtual Host was created');
    }

    public function getSandboxHosts(Request $request)
    {
        $sld = $request->input('sld');
        $tld = $request->input('tld');

        $namecheapservice = new NamecheapService;

        return $namecheapservice->getHostsSandbox($sld, $tld);
    }
}
