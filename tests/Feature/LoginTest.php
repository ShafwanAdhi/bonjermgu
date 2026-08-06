<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('budisantoso|127.0.0.1');
});

it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Masuk')
        ->assertSee('Tampilkan kata sandi');
});

it('logs in with correct credentials', function () {
    $user = User::factory()->referral()->create([
        'username' => 'budisantoso',
        'password' => '00000003',
        'remember_token' => null,
    ]);

    Livewire::test(Login::class)
        ->set('username', 'budisantoso')
        ->set('password', '00000003')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id);
});

it('can remember the authenticated user', function () {
    $user = User::factory()->referral()->create([
        'username' => 'budisantoso',
        'password' => '00000003',
        'remember_token' => null,
    ]);

    Livewire::test(Login::class)
        ->set('username', 'budisantoso')
        ->set('password', '00000003')
        ->set('remember', true)
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id)
        ->and($user->fresh()->remember_token)->not->toBeNull();
});

it('rejects a wrong password without saying which field was wrong', function () {
    User::factory()->create([
        'username' => 'budisantoso',
        'password' => '00000003',
    ]);

    Livewire::test(Login::class)
        ->set('username', 'budisantoso')
        ->set('password', '99999999')
        ->call('login')
        ->assertHasErrors('username');

    expect(auth()->check())->toBeFalse();
});

it('rejects an unknown username', function () {
    Livewire::test(Login::class)
        ->set('username', 'tidakada')
        ->set('password', '00000003')
        ->call('login')
        ->assertHasErrors('username');

    expect(auth()->check())->toBeFalse();
});

it('throttles after five failed attempts', function () {
    User::factory()->create([
        'username' => 'budisantoso',
        'password' => '00000003',
    ]);

    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)
            ->set('username', 'budisantoso')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors('username');
    }

    // The sixth attempt is refused before the credentials are even checked,
    // so a correct password does not get through either.
    Livewire::test(Login::class)
        ->set('username', 'budisantoso')
        ->set('password', '00000003')
        ->call('login')
        ->assertHasErrors('username');

    expect(auth()->check())->toBeFalse();
});

it('refuses an inactive account even with the right password', function () {
    User::factory()->inactive()->create([
        'username' => 'budisantoso',
        'password' => '00000003',
    ]);

    Livewire::test(Login::class)
        ->set('username', 'budisantoso')
        ->set('password', '00000003')
        ->call('login')
        ->assertHasErrors('username');

    expect(auth()->check())->toBeFalse();
});

it('requires both fields', function () {
    Livewire::test(Login::class)
        ->call('login')
        ->assertHasErrors(['username' => 'required', 'password' => 'required']);
});

it('logs the user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect(route('landing'));

    expect(auth()->check())->toBeFalse();
});
