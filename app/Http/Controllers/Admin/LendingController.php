<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LendingController extends Controller
{
    public function perOfficer(): View
    {
        return $this->report('ao');
    }

    public function perReferral(): View
    {
        return $this->report('referral');
    }

    private function report(string $tab): View
    {
        return view('admin.lending', [
            'tab' => $tab,
        ]);
    }
}
