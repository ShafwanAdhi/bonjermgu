<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Lending\LendingFilters;
use App\Domain\Lending\LendingQuery;
use App\Http\Controllers\Controller;
use App\Models\ReferralCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LendingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tab = $request->query('tab') === 'referral' ? 'referral' : 'ao';

        $filters = LendingFilters::fromArray($request->only([
            'from', 'to', 'product', 'category_id',
        ]));

        $rows = $tab === 'ao'
            ? LendingQuery::perOfficer($filters)
            : LendingQuery::perReferral($filters);

        return view('admin.lending', [
            'tab' => $tab,
            'rows' => $rows,
            'totals' => LendingQuery::totals($filters),
            'nameHeading' => $tab === 'ao' ? 'Referral' : 'Account Officer',
            'filters' => $filters,
            'categories' => ReferralCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
