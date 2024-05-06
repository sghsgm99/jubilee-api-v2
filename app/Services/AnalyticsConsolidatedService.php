<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Site;
use Carbon\Carbon;
use Analytics;
use Spatie\Analytics\Period;

class AnalyticsConsolidatedService
{
    /**
     * @var string $view_id
     */
    private $view_id;

    /**
     * @var string $service_account_credentials_json
     */
    private $service_account_credentials_json;

    public static function resolve(Account $account): AnalyticsConsolidatedService
    {
        /** @var AnalyticsConsolidatedService $service */
        $service = app(self::class);
        $service->view_id = $account->view_id;
        $service->service_account_credentials_json = $account->analytic_file;

        if (! $account->view_id && ! $account->analytic_file) {
            throw new \InvalidArgumentException('No analytics is currently setup');
        }

        // override analytics config, so we can implement it per site
        config([
            'analytics.view_id' => $service->view_id,
            'analytics.service_account_credentials_json' => $service->service_account_credentials_json,
        ]);

        return $service;
    }

    public function getAllSitesAvailable()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        $response = Analytics::performQuery(
            Period::create($startDate, $endDate),
            'ga:pageValue',
            [
                'dimensions' => 'ga:hostname',
            ]
        );

        return collect($response)->map(function (array $item) {
            return [
                'value' => $item[0],
                'label'  => $item[0]
            ];
        });
    }

    public function getOverallTotalVisitorsAndPageViews(Carbon $startDate, Carbon $endDate, string $hostname = null): array
    {
        $chart = [];
        $totalSession = 0;

        try {
            $others = [
                'dimensions' => 'ga:date',
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:users,ga:pageviews',
                $others
            );

            foreach ($response as $item) {
                $totalSession += (int) $item[2];

                $chart[] = [
                    Carbon::parse($item[0])->format('d M'),
                    $item[1],
                ];
            }
        } catch (\Exception $e) {
            // do nothing if no analytics is currently setup
        }

        return [
            'chart' => $chart,
            'session' => $totalSession
        ];
    }

    public function getOverallTopReferrers(Carbon $startDate, Carbon $endDate, string $hostname = null, int $maxResults = 20): array
    {
        try {
            $data = [];

            $others = [
                'dimensions' => 'ga:fullReferrer,ga:hostname',
                'sort' => '-ga:pageviews',
                'max-results' => $maxResults,
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:pageviews',
                $others
            );

            foreach ($response as $item) {
                $data[] = [
                    'url' => $item[0],
                    'hostname' => $item[1],
                    'pageViews' => (int) $item[2],
                ];
            }

            return [
                'total' => collect($data)->sum('pageViews'),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            // do nothing if no analytics is currently setup
        }

        return [
            'total' => 0,
            'data' => []
        ];
    }

    public function getOverallSessionByCountry(Carbon $startDate, Carbon $endDate, string $hostname = null): array
    {
        try {
            $others = [
                'dimensions' => 'ga:country',
                'sort' => '-ga:sessions',
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:sessions',
                $others
            );

            $data = collect($response['rows'] ?? [])->map(function (array $pageRow) {
                return [$pageRow[0], $pageRow[1]];
            });

            return array_merge([['Country', 'Popularity']], $data->toArray());

        } catch (\Exception $e) {
            // do nothing probably analytics is not yet installed
        }

        return [];
    }

    public function getOverallSessionByDevice(Carbon $startDate, Carbon $endDate, string $hostname = null): array
    {
        try {
            $others = [
                'dimensions' => 'ga:deviceCategory'
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:sessions',
                $others
            );

            $total = 0;
            $desktop = 0;
            $table = 0;
            $mobile = 0;
            foreach ($response as $item) {
                if ($item[0] == 'desktop') {
                    $desktop = $item[1];
                } elseif ($item[0] == 'mobile') {
                    $mobile = $item[1];
                } elseif ($item[0] == 'tablet') {
                    $table = $item[1];
                }

                $total += $item[1];
            }

            // TODO need to calculate for trend
            return [
                ['device' => 'Desktop', 'percentage' => number_format(($desktop / $total * 100), 2)], // 'trend' => 1
                ['device' => 'Mobile', 'percentage' => number_format(($mobile / $total * 100), 2)], // 'trend' => 9
                ['device' => 'Tablet', 'percentage' => number_format(($table / $total * 100), 2)] // 'trend' => -3
            ];
        } catch (\Exception $e) {
            // fallback below in case analytics is not yet installed
        }

        return [];
    }

    public function getOverallTrafficSource(Carbon $startDate, Carbon $endDate, string $hostname = null): array
    {
        try {
            $others = [
                'dimensions' => 'ga:source,ga:medium',
                'sort' => 'ga:sessions',
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:sessions,ga:pageviews,ga:sessionDuration,ga:exits',
                $others
            );

            $organic = 0;
            $direct = 0;
            $referral = 0;
            $social = 0;
            $others = 0;

            foreach ($response as $item) {
                if ($item[0] === '(direct)') {
                    $direct++;
                    continue;
                }

                switch ($item[1]) {
                    case 'referral': $referral++;
                        break;
                    case 'organic': $organic++;
                        break;
                    case 'social': $social++;
                        break;
                    default: $others++;
                        break;
                }
            }

            return [
                ['', ''],
                ['Organic', $organic],
                ['Direct', $direct],
                ['Referral', $referral],
                ['Social', $social],
                ['Others', $others]
            ];
        } catch (\Exception $e) {
            // do nothing probably analytics is not yet installed
        }

        return [];
    }

    public function getOverallMostVisitedPages(Carbon $startDate, Carbon $endDate, string $hostname = null, int $maxResults = 20): array
    {
        $data = [];
        try {
            $others = [
                'dimensions' => 'ga:pagePath,ga:pageTitle,ga:hostname',
                'sort' => '-ga:pageviews',
                'max-results' => $maxResults,
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:pageviews,ga:avgSessionDuration,ga:bounceRate',
                $others
            );

            foreach ($response as $item) {
                $data[] = [
                    'siteId' => Site::whereUrl($item[2])->first()->id ?? null,
                    'page' => $item[0],
                    'pageTitle' => $item[1],
                    'hostname' => $item[2],
                    'pageViews' => (int) $item[3],
                    'avgSession' => number_format($item[4]) . 's',
                    'avgBounceRate' => number_format($item[5], 2) . '%'
                ];
            }
        } catch (\Exception $e) {
            // do nothing if no analytics is currently setup
        }

        return $data;
    }

    public function getOverallMonthlyWeeklyDailyVisitors(Carbon $startDate, Carbon $endDate, string $hostname = null): array
    {
        $data = [];

        try {
            $others = [
                'dimensions' => 'ga:date',
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $result = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:users,ga:pageviews',
                $others
            );

            $response = collect($result['rows'] ?? [])->map(function (array $dateRow) {
                return [
                    'date' => Carbon::createFromFormat('Ymd', $dateRow[0]),
                    'visitors' => (int) $dateRow[1],
                    'pageViews' => (int) $dateRow[2],
                ];
            });

            $interval = $startDate->diffInDays($endDate);
            if ($interval <= 7) {
                $data[] = ['Day', 'Visitors', 'Page Views'];

                foreach ($response as $item) {
                    $data[] = [
                        Carbon::parse($item['date'])->format('d M'),
                        $item['visitors'],
                        $item['pageViews'],
                    ];
                }
            } elseif ($interval <= 30) {
                $flag = 1;
                $data[] = ['Week', 'Visitors', 'Page Views'];

                foreach ($response as $item) {
                    $weekIndex = Carbon::parse($item['date'])->format('WY');

                    if (isset($data[$weekIndex])) {
                        $data[$weekIndex][1] += $item['visitors'];
                        $data[$weekIndex][2] += $item['pageViews'];

                        continue;
                    }

                    $data[$weekIndex] = [
                        $flag++,
                        $item['visitors'],
                        $item['pageViews'],
                    ];
                }
            } else {
                $data[] = ['Month', 'Visitors', 'Page Views'];

                foreach ($response as $item) {
                    $monthIndex = Carbon::parse($item['date'])->format('mY');

                    if (isset($data[$monthIndex])) {
                        $data[$monthIndex][1] += $item['visitors'];
                        $data[$monthIndex][2] += $item['pageViews'];

                        continue;
                    }

                    $data[$monthIndex] = [
                        Carbon::parse($item['date'])->format('M Y'),
                        $item['visitors'],
                        $item['pageViews'],
                    ];
                }
            }
        } catch (\Exception $e) {
            // do nothing if no analytics is currently setup
        }

        return array_values($data);
    }

    public function getOverallSummaryInfo(Carbon $startDate, Carbon $endDate, string $hostname = null): array
    {
        try {
            $others = [
                'dimensions' => 'ga:pagePath,ga:pageTitle,ga:deviceCategory,ga:source,ga:hostname',
                'sort' => '-ga:pageviews',
            ];

            if ($hostname) {
                $others['filters'] = "ga:hostname=~^{$hostname}";
            }

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:pageviews',
                $others
            );

            $topResult = collect($response)->first();
            $secondResult = collect($response)[1];

            return [
                'username' => auth()->user()->first_name,
                'totalVisits' => $topResult[5] ?? 0,
                'device' => ucwords($topResult[2]) ?? '',
                'page' => $topResult[0] ?? '',
                'hostname' => $topResult[4],
                'source1' => ucwords(str_replace(['(', ')'], '', $topResult[3])) ?? '',
                'source2' => ucwords(str_replace(['(', ')'], '', $secondResult[3])) ?? '',
            ];
        } catch (\Exception $e) {
            // do nothing probably analytics is not yet installed
        }

        return [
            'username' => auth()->user()->first_name,
            'totalVisits' => 0,
            'device' => '',
            'page' => '',
            'source1' => '',
            'source2' => ''
        ];
    }
}
