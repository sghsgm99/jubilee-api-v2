<?php

return [
    'url' => env('REPO_URL', 'https://api.github.com/repos/Disrupt-Social-Team/jubilee-cms/actions/workflows/deploy.yml/dispatches'),
    'ref' => env('REPO_REF','master'),
    'apiroot' => env('REPO_APIROOT','https://dev.jubile.io/api/v1/'),
    'token' => env('REPO_TOKEN', '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'),
    'site_endpoint' => env('REPO_SITE_ENDPOINT', 'https://api.jubilee.news/api/v1/webhooks/site'),
    'article_endpoint' => env('REPO_ARTICLE_ENDPOINT', 'https://api.jubilee.news/api/v1/webhooks/article'),
    'builder_endpoint' => env('REPO_BUILDER_ENDPOINT', 'https://api.jubilee.news/api/v1/webhooks/builder'),
];
