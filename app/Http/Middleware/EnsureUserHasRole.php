<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates whole sections by role — a Referral has no business on a configuration
 * page at all, so 403 is the honest answer (docs/pages.md section 18).
 *
 * This is NOT the authorization that matters most. Record-level ownership is
 * enforced on the query through a global scope, backed by a Policy, per AD-09.
 * That layer does not exist yet because the application tables do not exist
 * yet. Do not treat this middleware as a substitute when they land.
 *
 * Note the different answer for record-level denial: another officer's
 * application returns 404, not 403, so the response does not reveal that the
 * record exists.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
