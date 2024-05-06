<?php

namespace App\Services;

use App\Models\Site;
use Carbon\Carbon;
use Analytics;
use Illuminate\Support\Collection;
use Spatie\Analytics\Period;

class AnalyticsService
{
    /**
     * @var string $view_id
     */
    private $view_id;

    /**
     * @var string $service_account_credentials_json
     */
    private $service_account_credentials_json;

    public static function resolve(Site $site): AnalyticsService
    {
        /** @var AnalyticsService $analyticsService */
        $analyticsService = app(self::class);
        $analyticsService->view_id = $site->view_id;
        $analyticsService->service_account_credentials_json = $site->analytic_file;

        if (!$site->view_id && !$site->analytic_file) {
            throw new \InvalidArgumentException('No site analytics is currently setup');
        }

        // override analytics config, so we can implement it per site
        config([
            'analytics.view_id' => $analyticsService->view_id,
            'analytics.service_account_credentials_json' => $analyticsService->service_account_credentials_json,
        ]);

        return $analyticsService;
    }

    public function getTotalVisitorsAndPageViews(Carbon $startDate, Carbon $endDate): array
    {
        $chart = [];
        $totalSession = 0;

        try {
            $response = Analytics::fetchTotalVisitorsAndPageViews(Period::create($startDate, $endDate));

            foreach ($response as $item) {
                $totalSession += $item['pageViews'];

                $chart[] = [
                    Carbon::parse($item['date'])->format('d M'),
                    $item['visitors'],
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

    public function getTopReferrers(Carbon $startDate, Carbon $endDate, int $maxResults = 20): array
    {
        try {
            $response = Analytics::fetchTopReferrers(Period::create($startDate, $endDate), $maxResults);

            return [
                'total' => $response->sum('pageViews'),
                'data' => $response->toArray()
            ];


        } catch (\Exception $e) {
            // do nothing if no analytics is currently setup
        }

        return [
            'total' => 0,
            'data' => []
        ];
    }

    public function getMostVisitedPages(Carbon $startDate, Carbon $endDate, int $maxResults = 20): array
    {
        $data = [];
        try {
            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:pageviews,ga:avgSessionDuration,ga:bounceRate',
                [
                    'dimensions' => 'ga:pagePath,ga:pageTitle',
                    'sort' => '-ga:pageviews',
                    'max-results' => $maxResults,
                ]
            );

            foreach ($response as $item) {
                $data[] = [
                    'page' => $item[0],
                    'pageViews' => (int) $item[2],
                    'avgSession' => number_format($item[3]) . 's',
                    'avgBounceRate' => number_format($item[4], 2) . '%'
                ];
            }
        } catch (\Exception $e) {
            // do nothing if no analytics is currently setup
        }

        return $data;
    }

    public function getSessionByDevice(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:sessions',
                [
                    'dimensions' => 'ga:deviceCategory'
                ]
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

            if( $total === 0 ){
                return [
                    [ 'device' => 'Desktop', 'percentage' => 0],
                    [ 'device' => 'Mobile', 'percentage' => 0],
                    [ 'device' => 'Tablet', 'percentage' => 0]
                ];
            }

            // TODO need to calculate for trend
            return [
                [ 'device' => 'Desktop', 'percentage' => number_format(($desktop / $total * 100), 2)], // 'trend' => 1
                [ 'device' => 'Mobile', 'percentage' => number_format(($mobile / $total * 100), 2)], // 'trend' => 9
                [ 'device' => 'Tablet', 'percentage' => number_format(($table / $total * 100), 2)] // 'trend' => -3
            ];
        } catch (\Exception $e) {
            // fallback below in case analytics is not yet installed
        }

        return [];
    }

    public function getSessionByCountry(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:sessions',
                [
                    'dimensions' => 'ga:country',
                    'sort' => '-ga:sessions',
                ]
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

    public function getTrafficSource(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $organic = 0;
            $direct = 0;
            $referral = 0;
            $social = 0;
            $others = 0;

            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:sessions,ga:pageviews,ga:sessionDuration,ga:exits',
                [
                    'dimensions' => 'ga:source,ga:medium',
                    'sort' => 'ga:sessions',
                ]
            );

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

    public function getMonthlyWeeklyDailyVisitors(Carbon $startDate, Carbon $endDate): array
    {
        $data = [];

        try {
            $response = Analytics::fetchTotalVisitorsAndPageViews(Period::create($startDate, $endDate));
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

    public function getSummaryInfo(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $response = Analytics::performQuery(
                Period::create($startDate, $endDate),
                'ga:pageviews',
                [
                    'dimensions' => 'ga:pagePath,ga:pageTitle,ga:deviceCategory,ga:source',
                    'sort' => '-ga:pageviews',
                ]
            );

            $topResult = collect($response)->first();
            $secondResult = collect($response)[1];

            return [
                'username' => auth()->user()->first_name,
                'totalVisits' => $topResult[4] ?? 0,
                'device' => ucwords($topResult[2]) ?? '',
                'page' => $topResult[0] ?? '',
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

    /**
     * REAL TIME ANALYTICS API
     */
    public function performRealTimeQuery(string $metrics, array $others = [])
    {
        $service = Analytics::getAnalyticsService();

        return $service->data_realtime->get(
            'ga:' . $this->view_id,
            $metrics,
            $others
        );
    }

    public function getRealTimeData(): array
    {
        try {
            $response = $this->performRealTimeQuery('rt:activeUsers', [
                'dimensions' => 'rt:pagePath, rt:deviceCategory, rt:country',
                'sort' => '-rt:activeUsers',
            ]);

            $results = collect($response['rows'] ?? [])->map(function (array $row) {
                return [
                    'url' => $row[0],
                    'device' => $row[1],
                    'country' => $row[2],
                    'users' => (int) $row[3],
                ];
            })->toArray();

            $total_users = $response->totalsForAllResults['rt:activeUsers'];

            [$pages, $devices, $locations] = $this->getPageDeviceCountryInfo($total_users, $results);

            return [
                'total_users' => $total_users,
                'devices' => $devices,
                'pages' => $pages,
                'locations' => $locations,
            ];
        } catch (\Exception $e) {
             // do nothing if no analytics is currently setup
        }

        return [
            'total_users' => 0,
            'devices' => [],
            'pages' => [],
            'locations' => [],
        ];
    }

    private function getPageDeviceCountryInfo(int $total_users, array $rows): array
    {
        $pages = [];
        $devices = [];
        $locations = [];

        foreach ($rows as $row) {
            $pages = $this->formatInfo($pages, $row['url'], $row['users'], 'url');
            $devices = $this->formatInfo($devices, ucfirst(strtolower($row['device'])), $row['users'], 'type');
            $locations = $this->formatInfo($locations, $row['country'], $row['users'], 'country');
        }

        $devices = collect($devices)->map(function ($item, $key) use ($total_users) {
            return [
                'type' => $item['type'],
                'users' => $item['users'],
                'percentage' => number_format(($item['users'] / $total_users) * 100, 0) . '%'
            ];
        });

        $pages = collect($pages)->sortBy('users', SORT_REGULAR, true);
        $devices = collect($devices)->sortBy('percentage', SORT_REGULAR, true);
        $locations = collect($locations)->sortBy('users', SORT_REGULAR, true);

        return [
            $pages->values()->all(),
            $devices->values()->all(),
            $locations->values()->all(),
        ];
    }

    private function formatInfo(array $type, string $key, int $value, string $label = null): array
    {
        $label = $label ?? 'label';

        if (! isset($type[$key])) {
            $type[$key] = [
                $label => $key,
                'users' => 0,
            ];
        }

        $type[$key]['users'] += $value;

        return $type;
    }
}
