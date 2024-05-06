<?php

namespace App\Http\Middleware;

use App\Models\BuilderSite;
use Closure;
use App\Models\Site;
use Illuminate\Http\Request;

class BuilderToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('token');

        if (! $request->has('token') || empty($token)) {
            return abort(404);
        }

        $builderSite = BuilderSite::whereToken($token)->firstOrFail();

        $request->merge(array('builder_site' => $builderSite));

        return $next($request);
    }
}

