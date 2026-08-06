<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * One /profile route, different content per role (docs/pages.md section 2).
 * Admin has no profile screen of its own — actors.md gives Admin no such page.
 */
class ProfileController extends Controller
{
    public function __invoke(): View
    {
        // Not named $component — Blade reserves that inside x-components and
        // the layout would swallow it.
        $profileComponent = match (Auth::user()->role) {
            Role::Referral => 'profile.referral-profile',
            Role::AccountOfficer => 'profile.officer-profile',
            Role::Admin => abort(403),
        };

        return view('profile.show', ['profileComponent' => $profileComponent]);
    }
}
