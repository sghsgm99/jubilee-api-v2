<?php

namespace App\Models\Services;

use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;

class CManagerService extends ModelService
{
    public static function getMediaBuyers()
    {
        $buyerList = [
            'data' => [],
            'totals' => [
                'impressions' => 0,
                'clicks' => 0,
                'spend' => 0,
                'revenue' => 0,
                'profit' => 0
            ]
        ];

        $accounts = Account::all();

        foreach($accounts as $account) {
            $buyerList['data'][] = [
                'buyer' => $account->company_name,
                'total_reach' => 0,
                'total_impression' => 0,
                'total_purchase' => 0,
                'cpc' => 0,
                'rpc' => 0,
                'total_spend' => 0,
                'total_revenue' => 0,
                'total_profit' => 0,
                'roi' => 0,
            ];
        }

        return $buyerList;
    }
}
