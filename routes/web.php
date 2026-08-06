<?php

use App\Http\Controllers\Admin\LendingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\VehicleCascadeController;
use App\Livewire\Admin\Accounts\OfficerAccounts;
use App\Livewire\Admin\Accounts\ReferralAccounts;
use App\Livewire\Admin\Configuration\Defaults as ConfigurationDefaults;
use App\Livewire\Admin\Configuration\Fees as ConfigurationFees;
use App\Livewire\Admin\Configuration\Insurance as ConfigurationInsurance;
use App\Livewire\Admin\Configuration\Products as ConfigurationProducts;
use App\Livewire\Admin\Master\Lookups as MasterLookups;
use App\Livewire\Admin\Master\ReferralMaster;
use App\Livewire\Admin\Master\Vehicles as MasterVehicles;
use App\Livewire\Admin\Simulation\ConfigurationSimulation;
use App\Livewire\Application\ApplicationDetail;
use App\Livewire\Application\ApplicationList;
use App\Livewire\Application\CreateApplication;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Simulation\CreditSimulation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
|
| Free access. Every other page requires authentication, per docs/pages.md.
|
*/

Route::view('/', 'landing')->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('landing');
})->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Bersama
|--------------------------------------------------------------------------
|
| One route, different content per role — docs/pages.md section 2.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', ProfileController::class)->name('profile');
});

/*
|--------------------------------------------------------------------------
| Simulasi Kredit — Referral
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:referral'])->group(function () {
    Route::get('/simulation', CreditSimulation::class)->name('simulation');
    Route::get('/simulation/print', [SimulationController::class, 'print'])->name('simulation.print');
    Route::get('/simulation/print/download', [SimulationController::class, 'download'])->name('simulation.print.download');
    Route::prefix('/simulation/vehicle-options')->name('simulation.vehicles.')->group(function () {
        Route::get('/usages', [VehicleCascadeController::class, 'usages'])->name('usages');
        Route::get('/usages/{usage}/brands', [VehicleCascadeController::class, 'brands'])
            ->whereNumber('usage')->name('brands');
        Route::get('/brands/{brand}/types', [VehicleCascadeController::class, 'types'])
            ->whereNumber('brand')->name('types');
        Route::get('/types/{type}/models', [VehicleCascadeController::class, 'models'])
            ->whereNumber('type')->name('models');
        Route::get('/models/{model}/years', [VehicleCascadeController::class, 'years'])
            ->whereNumber('model')->name('years');
    });
});

/*
|--------------------------------------------------------------------------
| Aplikasi — Referral melihat, AO mengubah
|--------------------------------------------------------------------------
|
| Referral passes the role gate here and is refused the write actions further
| in. Seeing is allowed; changing is not (docs/actors.md section 2).
|
*/

Route::middleware(['auth', 'role:referral,ao'])->group(function () {
    Route::get('/applications', ApplicationList::class)->name('applications.index');
});

/*
 * Declared before the {application} route below. "create" matches the code
 * pattern, so if the wildcard is registered first it swallows this URL and
 * quietly serves a detail page instead.
 */
Route::middleware(['auth', 'role:ao'])->group(function () {
    Route::get('/applications/create', CreateApplication::class)->name('applications.create');
});

/*
 * Bound on the code, not the id. The visibility scope runs on the binding
 * query, so another owner's application resolves to 404 without the controller
 * having to check anything (AD-09, pages.md §18).
 */
Route::middleware(['auth', 'role:referral,ao'])->group(function () {
    Route::get('/applications/{application}', ApplicationDetail::class)
        ->where('application', '[A-Za-z0-9]{6}')
        ->name('applications.show');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| Admin has no route into application data at all. That absence is the design,
| not an oversight — see AD-09.
|
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/configuration/products', ConfigurationProducts::class)->name('configuration.products');
    Route::get('/configuration/insurance', ConfigurationInsurance::class)->name('configuration.insurance');
    Route::get('/configuration/fees', ConfigurationFees::class)->name('configuration.fees');
    Route::get('/configuration/defaults', ConfigurationDefaults::class)->name('configuration.defaults');
    // Uji Konfigurasi — Admin menjalankan engine atas Product terpilih.
    // Tanpa data debitur, tanpa cetak, tanpa penyimpanan hasil.
    Route::get('/configuration/simulation', ConfigurationSimulation::class)->name('configuration.simulation');

    Route::get('/master/vehicles', MasterVehicles::class)->name('master.vehicles');
    Route::get('/master/referral', ReferralMaster::class)->name('master.referral');
    Route::get('/master/lookups', MasterLookups::class)->name('master.lookups');

    Route::get('/accounts/referrals', ReferralAccounts::class)->name('accounts.referrals');
    Route::get('/accounts/officers', OfficerAccounts::class)->name('accounts.officers');

    Route::get('/lending', LendingController::class)->name('lending');
});
