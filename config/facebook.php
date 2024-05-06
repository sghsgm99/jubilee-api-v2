<?php

return [
    'sandbox' => env('FACEBOOK_SANDBOX_MODE', true),
    'version' => env('FACEBOOK_VERSION', 'v13.0'),
    'test_email' => 'test@jubileearb.app',
    'test_ad_account' => env('FACEBOOK_TEST_AD_ACCOUNT', '373004508185391'),
    'test_access_token' => env('FACEBOOK_TEST_ACCESS_TOKEN', 'EAANz3oYd8O4BABd8ZBtNZARMrAEo3j9rLQYWqRUt0mRUvJvM1OgEh9Ri6VxHRek886Lx3UeSxDF2lU2sHQaNXqtjgIVUr0WhNkY3wvviXaNyN3ZARANocD1MEEZBZBzWUodtRk2VNaq0M6kdPAJ1sSGyXLif5urJrj4yHHnCSCXln8O80FB5fgrMmCCr7FQMZD'),
    'test_facebook_page_id' => env('FACEBOOK_TEST_PAGE_ID', '100420619042796'),
    'parent_bm' => [
        'app_id' => env('FACEBOOK_APP_ID', NULL),
        'app_secret' => env('FACEBOOK_APP_SECRET', NULL),
        'business_manager_id' => env('FACEBOOK_BUSINESS_MANAGER_ID', null),
        'access_token' => env('FACEBOOK_ACCESS_TOKEN', null),
        'primary_page_id' => env('FACEBOOK_PRIMARY_PAGE_ID', null),
        'line_of_credit' => env('FACEBOOK_LINE_OF_CREDIT_ID', null)
    ],
];