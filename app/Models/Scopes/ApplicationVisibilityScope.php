<?php

namespace App\Models\Scopes;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Ownership enforced on the query, not in the controller — AD-09.
 *
 * A controller check is easy to forget: one new endpoint without an
 * authorize() call opens everything. This closes the hole from the other
 * direction, so a query that forgets to filter still returns nothing that does
 * not belong to the caller.
 *
 * Admin gets an EMPTY SET, not everything. That surprises people, and it is
 * the point: Admin has no access to application data at all. The single
 * exception is the Lending aggregate, which opts out explicitly with
 * withoutGlobalScope (AD-11). Do not use that anywhere else.
 */
class ApplicationVisibilityScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // No session, no rows. Never fall through to unfiltered.
        if (! $user) {
            $builder->whereRaw('1 = 0');

            return;
        }

        match ($user->role) {
            Role::AccountOfficer => $builder->where(
                $model->qualifyColumn('account_officer_id'),
                $user->accountOfficer?->id ?? 0,
            ),
            Role::Referral => $builder->where(
                $model->qualifyColumn('referral_id'),
                $user->referral?->id ?? 0,
            ),
            Role::Admin => $builder->whereRaw('1 = 0'),
        };
    }
}
