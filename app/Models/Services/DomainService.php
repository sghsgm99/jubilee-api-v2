<?php

namespace App\Models\Services;

use App\Models\User;
use App\Models\Domain;
use App\Models\SubDomain;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Enums\DNSStatusEnum;

class DomainService extends ModelService
{
    private $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
        $this->model = $domain; // required
    }

    public static function create(
        User $user,
        string $server,
        string $domainname,
        string $username,
        string $nameserver1 = null,
        string $nameserver2 = null,
        string $ipaddress = null
    )
    {
        $domain = new Domain();

        $domain->server = $server;
        $domain->domain = $domainname;
        $domain->username = $username;
        $domain->nameserver1 = $nameserver1;
        $domain->nameserver2 = $nameserver2;
        $domain->ipaddress = $ipaddress;
        $domain->user_id = $user->id;
        $domain->account_id = $user->account_id;
        $domain->status = DNSStatusEnum::INACTIVE;
        $domain->save();
        
        return $domain;
    }

    public function update(
        string $server,
        string $domainname,
        string $username,
        string $nameserver1 = null,
        string $nameserver2 = null,
        string $ipaddress = null
    )
    {
        $this->domain->server = $server;
        $this->domain->domain = $domainname;
        $this->domain->username = $username;
        $this->domain->nameserver1 = $nameserver1;
        $this->domain->nameserver2 = $nameserver2;
        $this->domain->ipaddress = $ipaddress;
        $this->domain->save();

        return $this->domain->fresh();
    }

    public function delete(): bool
    {
        if (SubDomain::where('domain_id', $this->domain->id)->exists()) {
            return false;
        }

        $this->domain->delete();
        return true;
    }
}
