<?php

namespace App\Services\Wordpress;

use App\Models\Article;
use App\Models\ArticleSite;
use App\Models\Enums\WordpressPostStatusEnum;
use App\Models\Site;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

/**
 * Class WordpressService.
 */
class WordpressService
{
    const AUTH_REQUEST_ROUTE = 'oauth1/request';
    const AUTH_AUTHORIZE_ROUTE = 'oauth1/authorize';
    const AUTH_ACCESS_ROUTE = 'oauth1/access';
    const API_ROUTE = 'wp-json/wp/v2/';

    /**
     * @var Site
     */
    private $site;

    /**
     * @var Client
     */
    private $auth_client;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $params;

    /**
     * @param Site $site
     * @return WordpressService
     */
    public static function resolve(Site $site)
    {
        /** @var WordpressService $wordpressService */
        $wordpressService = app(self::class);

        try {
            $wordpressService->params = [
                'consumer_key' => $site->client_key,
                'consumer_secret' => $site->client_secret_key,
                'signature_method' => Oauth1::SIGNATURE_METHOD_HMAC,
            ];

            if ($site->api_permissions && $site->api_permissions['oauth_token'] && $site->api_permissions['oauth_token_secret']) {
                $wordpressService->params['token'] = $site->api_permissions['oauth_token'];
                $wordpressService->params['token_secret'] = $site->api_permissions['oauth_token_secret'];
            }

            $middleware = new Oauth1($wordpressService->params);
            $stack = HandlerStack::create();
            $stack->push($middleware);

            $wordpressService->site = $site;
            $wordpressService->client = new Client();
            $wordpressService->auth_client = new Client([
                'base_uri' => $site->url,
                'handler' => $stack,
                'auth' => 'oauth',
                'headers' => ['Content-Type' => 'application/json']
            ]);
        } catch (\Throwable $throwable) {
            // abort(400, 'Article was not posted to the site. Wordpress site is not fully integrated.');
        }

        return $wordpressService;
    }

    /**
     * @param int $article_id
     *
     * @return ArticleSite|null
     */
    public function getArticleSite(int $article_id)
    {
        try {
            return ArticleSite::whereArticleId($article_id)
                ->whereSiteId($this->site->id)
                ->first();
        } catch (\Throwable $throwable) {

        }

        return null;
    }

    /**
     * @return Site
     */
    public function requestToken()
    {
        try {
            $this->params['callback'] = config('app.url') . 'webhooks/sites/wordpress-callback?id=' . $this->site->id;

            $response = $this->auth_client->post($this->site->url . $this::AUTH_REQUEST_ROUTE);
            $contents = $response->getBody()->getContents();

            $api_callback = $this->site->url . $this::AUTH_AUTHORIZE_ROUTE . '?' . $contents;
            parse_str($contents, $api_permissions);
            $api_permissions['is_verified'] = false;

            $this->site->api_callback = $api_callback;
            $this->site->api_permissions = $api_permissions;
            $this->site->save();
        } catch (\Throwable $throwable) {
            abort(400, $throwable->getMessage());
        }

        return $this->site->fresh();
    }

    /**
     * @param string $oauth_verifier
     *
     * @return Site
     */
    public function authorizeToken(string $oauth_verifier)
    {
        try {
            $request_url = $this->site->url . $this::AUTH_ACCESS_ROUTE . '?oauth_verifier=' . $oauth_verifier;
            $response = $this->auth_client->post($request_url);
            $contents = $response->getBody()->getContents();

            parse_str($contents, $api_permissions);
            $api_permissions['is_verified'] = true;

            $this->site->api_permissions = $api_permissions;
            $this->site->save();
        } catch (\Throwable $throwable) {
            abort(400, $throwable->getMessage());
        }

        return $this->site->fresh();
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        try {
            $response = $this->auth_client->get($this->site->url . $this::API_ROUTE . 'tags');
            $result = json_decode($response->getBody()->getContents());
        } catch (\Throwable $throwable) {
//            abort(400, $throwable->getMessage());
        }

        return $result ?? [];
    }

    /**
     * @return mixed
     */
    public function getCategories()
    {
        try {
            $response = $this->auth_client->get($this->site->url . $this::API_ROUTE . 'categories');
            $result = json_decode($response->getBody()->getContents());
        } catch (\Throwable $throwable) {
//            abort(400, $throwable->getMessage());
        }

        return $result ?? [];
    }

    /**
     * @return mixed
     */
    public function getPosts()
    {
        try {
            $response = $this->client->get($this->site->url . $this::API_ROUTE . 'posts');
            $result = json_decode($response->getBody()->getContents());
        } catch (\Throwable $throwable) {
//            abort(400, $throwable->getMessage());
        }

        return $result ?? [];
    }

    /**
     * @param Article $article
     *
     * @return mixed
     */
    public function getPost(Article $article)
    {
        try {
            $article_site = $this->getArticleSite($article->id);
            $response = $this->client->get($this->site->url . $this::API_ROUTE . 'posts/' . $article_site->external_post_id);
            $result = json_decode($response->getBody()->getContents());
        } catch (\Throwable $throwable) {
//            abort(400, $throwable->getMessage());
        }

        return $result ?? [];
    }

    /**
     * @param Article $article
     *
     * @return mixed
     */
    public function createPost(Article $article)
    {
        try {
            $article_site = $this->getArticleSite($article->id);
            $status = WordpressPostStatusEnum::memberByValue($article_site->status)->getKey();

            $response = $this->auth_client->post($this->site->url . $this::API_ROUTE . 'posts', [
                'form_params' => [
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'content' => $article->content,
                    'tags' => $article_site->tag_ids,
                    'menus' => $article_site->menu_ids,
                    'categories' => $article_site->category_ids,
                    'date' => now()->toDateTimeString(),
                    'featured_media' => $article->featureImage->path ?? null,
                    'status' => strtolower($status)
                ]
            ]);

            $result = json_decode($response->getBody()->getContents());

            $article_site->external_post_id = $result->id;
            $article_site->save();
        } catch (\Throwable $throwable) {
            abort(400, $throwable->getMessage());
        }

        return $result ?? [];
    }

    /**
     * @param Article $article
     *
     * @return mixed
     */
    public function updatePost(Article $article)
    {
        try {
            $article_site = $this->getArticleSite($article->id);
            $status = WordpressPostStatusEnum::memberByValue($article_site->status)->getKey();

            $response = $this->auth_client->post($this->site->url . $this::API_ROUTE . 'posts/' . $article_site->external_post_id, [
                'form_params' => [
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'content' => $article->content,
                    'tags' => $article_site->tag_ids,
                    'menus' => $article_site->menu_ids,
                    'categories' => $article_site->category_ids,
                    'featured_media' => $article->featureImage->path ?? null,
                    'status' => strtolower($status)
                ]
            ]);

            $result = json_decode($response->getBody()->getContents());
        } catch (\Throwable $throwable) {
            abort(400, $throwable->getMessage());
        }

        return $result ?? [];
    }

    /**
     * @param Article $article
     *
     * @return mixed
     */
    public function deletePost(Article $article)
    {
        try {
            $article_site = $this->getArticleSite($article->id);
            $response = $this->auth_client->delete($this->site->url . $this::API_ROUTE . 'posts/' . $article_site->external_post_id);
            $result = json_decode($response->getBody()->getContents());
        } catch (\Throwable $throwable) {
            // TODO return error message
        }

        return $result ?? [];
    }
}
