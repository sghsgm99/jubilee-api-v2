<?php

namespace App\Models\Services;

use App\Models\Domain;
use App\Models\SubDomain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Enums\DNSStatusEnum;

class SubDomainService extends ModelService
{
    private $subdomain;

    public function __construct(SubDomain $subdomain)
    {
        $this->subdomain = $subdomain;
        $this->model = $subdomain; // required
    }

    public static function create(
        Domain $domain,
        string $name
    )
    {
        $subdomain = new SubDomain();

        $subdomain->domain_id = $domain->id;
        $subdomain->name = $name;
        $subdomain->status = DNSStatusEnum::INACTIVE;
        $subdomain->user_id = auth()->user()->id;
        $subdomain->account_id = auth()->user()->account_id;
        $subdomain->save();

        return $subdomain;
    }

    public function update(string $name)
    {
        $this->subdomain->name = $name;
        $this->subdomain->save();

        return $this->subdomain->fresh();
    }

    public function active()
    {
        $this->subdomain->status = DNSStatusEnum::ACTIVE;
        $this->subdomain->save();

        return $this->subdomain->fresh();
    }
}
