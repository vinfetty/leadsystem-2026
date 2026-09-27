<?php

namespace App\Http\Middleware;

use App\Models\LeadSource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateLeadSource
{
    /**
     * Identify the sending site from its bearer token.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $source = LeadSource::findByToken($request->bearerToken());

        abort_if($source === null, Response::HTTP_UNAUTHORIZED, 'Unknown or inactive lead source.');

        $request->attributes->set('leadSource', $source);

        return $next($request);
    }
}
