<?php

namespace App\Http\Middleware;

use App\Support\Demo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscourageIndexing
{
    /**
     * Ask search engines to leave the demo out of their results.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Demo::enabled()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
