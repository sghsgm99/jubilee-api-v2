<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Domain;
use App\Services\ResponseService;
use App\Http\Resources\DomainResource;
use App\Models\Services\DomainService;
use App\Http\Requests\CreateDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Models\Enums\DNSStatusEnum;
use App\Services\NamecheapService;

class DomainController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('domains', [DomainController::class, 'create']);
        Route::get('domains', [DomainController::class, 'getCollection']);
        Route::get('domains/{domain}', [DomainController::class, 'get']);
        Route::put('domains/{domain}', [DomainController::class, 'update']);
        Route::delete('domains/{domain}', [DomainController::class, 'delete']);
        Route::get('domains/namecheap/{domain}', [DomainController::class, 'setHost']);
    }

    public function create(CreateDomainRequest $request)
    {
        $domain = DomainService::create(
            Auth::user(),
            $request->validated()['server'],
            $request->validated()['domain'],
            $request->validated()['username'],
            $request->nameserver1,
            $request->nameserver2,
            $request->ipaddress
        );
        
        return ResponseService::successCreate('Domain was created.', new DomainResource($domain));
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $domains = Domain::search($search, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return DomainResource::collection($domains);
    }

    public function get(Domain $domain)
    {
        return ResponseService::success('Success', new DomainResource($domain));
    }

    public function update(UpdateDomainRequest $request, Domain $domain)
    {
        $domain = $domain->Service()->update(
            $request->validated()['server'],
            $request->validated()['domain'],
            $request->validated()['username'],
            $request->nameserver1,
            $request->nameserver2,
            $request->ipaddress
        );

        if (isset($domain['error'])) {
            return ResponseService::serverError($domain['message']);
        }

        return ResponseService::successCreate('Domain was updated.', new DomainResource($domain));
    }

    public function delete(Domain $domain)
    {
        if (!$domain->Service()->delete()) {
            return ResponseService::serverError('Domain cannot be deleted because of a relationship data with Subdomains');
        }

        return ResponseService::success('Domain was archived.');
    }

    public function setHost(Domain $domain)
    {
        $sld = explode('.', $domain['domain'])[0];
        $tld = explode('.', $domain['domain'])[1];
        $hostName = ['HostName1' => '@'];
        $recordType = ['RecordType1' => 'A'];
        $address = ['Address1' => $domain['ipaddress']];

        $i = 2;
        foreach($domain->subdomains as $subdomain) {
            $hostName['HostName'.$i] = $subdomain->name;
            $recordType['RecordType'.$i] = 'CNAME';
            $address['Address'.$i] = $domain['domain'];

            $i++;
        }

        $namecheapservice = new NamecheapService;
        $dnsRecord = $namecheapservice->addRecord($sld, $tld, $hostName, $recordType, $address);

        return ResponseService::success('Add DNS Record', $dnsRecord);
    }
}
