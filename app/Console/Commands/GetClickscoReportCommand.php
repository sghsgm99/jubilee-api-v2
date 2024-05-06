<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ClickscoService;
use App\Models\Account;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GetClickscoReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:get_clicksco_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clicksco Report';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $account = Account::query()->where('id', 1)->first();

        if (! $account) {
            $this->info('No Account Found.');
            return CommandAlias::FAILURE;
        }

        $response = ClickscoService::resolve($account)->getReport();

        if ($response['success']) {
            $this->info('Retrieving reports from Clicksco successful.');
            return CommandAlias::SUCCESS;
        }

        $this->info($response['message']);
        return CommandAlias::FAILURE;
    }
}
