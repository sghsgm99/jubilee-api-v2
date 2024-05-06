<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\CleanUpArchiveArticlesCommand;
use App\Console\Commands\GetBingReportCommand;
use App\Console\Commands\GetGoogleReportCommand;
use App\Console\Commands\GetProgrammaticReportCommand;
use App\Console\Commands\GetYahooReportCommand;
use App\Console\Commands\ProcessRuleAutomationsCommand;
use App\Console\Commands\ProcessRuleDurationCommand;
use App\Console\Commands\ProcessGoogleHourlyAutomationCommand;
use App\Models\GoogleRuleAutomation;
use App\Models\Enums\GoogleRuleFrequencyEnum;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;
use DateTime;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /** WEEKLY COMMANDS  */

        /** DAILY COMMANDS */
        $schedule->call(function () {
            Artisan::call('view:clear'); // clear compiled views
            Artisan::call('cache:clear'); // clear application cache
            Artisan::call('config:clear'); // clear configuration cache
        })->daily();

        //$schedule->command(CleanUpArchiveArticlesCommand::class)->daily();
        //$schedule->command(GetYahooReportCommand::class)->dailyAt('23:00');
        //$schedule->command(GetBingReportCommand::class)->dailyAt('23:05');

        /** HOURLY COMMANDS */
        //$schedule->command(ProcessGoogleHourlyAutomationCommand::class)->hourly();
        //$schedule->command(GetGoogleReportCommand::class)->hourly();
        //$schedule->command(GetProgrammaticReportCommand::class)->hourly();

        /** EVERY 15 MINUTES COMMANDS */
        //$schedule->command(ProcessRuleAutomationsCommand::class)->everyFifteenMinutes();
        //$schedule->command(ProcessRuleDurationCommand::class)->everyFifteenMinutes();

        /** EVERY MINUTE COMMANDS */


        $ruleAutomations = GoogleRuleAutomation::query();

        $currentDateTime = new DateTime();
        
        foreach ($ruleAutomations->cursor() as $ruleAutomation) {
            if ($ruleAutomation->status == CampaignStatus::ENABLED) {
                Auth::loginUsingId($ruleAutomation->user_id);

                switch (GoogleRuleFrequencyEnum::memberByValue($ruleAutomation->frequency)) {
                    case GoogleRuleFrequencyEnum::ONCE():
                        $label = GoogleRuleFrequencyEnum::ONCE()->getLabel();
                        $val = $ruleAutomation->updated_at->modify('+1 minutes')->format('i H d m *');

                        $schedule->call(fn() => $ruleAutomation->Service()->processAutomationEx($label))->cron($val);
                        break;
                    case GoogleRuleFrequencyEnum::HOURLY():
                        $label = GoogleRuleFrequencyEnum::HOURLY()->getLabel();
                        $val = $ruleAutomation->updated_at->format('i');

                        $schedule->call(fn() => $ruleAutomation->Service()->processAutomationEx($label))->hourlyAt($val);
                        break;
                    case GoogleRuleFrequencyEnum::DAILY():
                        $label = GoogleRuleFrequencyEnum::DAILY()->getLabel();
                        $val = $ruleAutomation->updated_at->format('H:i');

                        $schedule->call(fn() => $ruleAutomation->Service()->processAutomationEx($label))->dailyAt($val);
                        break;
                    case GoogleRuleFrequencyEnum::WEEKLY():
                        $label = GoogleRuleFrequencyEnum::WEEKLY()->getLabel();
                        $val = $ruleAutomation->updated_at->format('H:i');
                        $week = $ruleAutomation->updated_at->dayOfWeek;

                        $schedule->call(fn() => $ruleAutomation->Service()->processAutomationEx($label))->weeklyOn($week, $val);
                        break;
                    case GoogleRuleFrequencyEnum::MONTHLY():
                        $label = GoogleRuleFrequencyEnum::MONTHLY()->getLabel();
                        $val = $ruleAutomation->updated_at->format('H:i');
                        $day = $ruleAutomation->updated_at->format('d');

                        $schedule->call(fn() => $ruleAutomation->Service()->processAutomationEx($label))->monthlyOn($day, $val);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        return 'America/Los_Angeles';
    }
}
