<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Profile\AdminProfile;
use App\Livewire\Profile\ReferralProfile;
use App\Mail\AccountPasswordResetMail;
use App\Models\AccountPasswordReset;
use App\Models\Admin;
use App\Models\Referral;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('renders the forgot password link from login', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Lupa kata sandi?');
});

it('sends a password reset email to the profile email', function () {
    Mail::fake();

    $referral = Referral::factory()->create([
        'email' => 'budi@example.test',
    ]);
    $referral->user->update(['username' => 'budisantoso']);

    Livewire::test(ForgotPassword::class)
        ->set('username', 'budisantoso')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    expect(AccountPasswordReset::where('user_id', $referral->user_id)->exists())->toBeTrue();

    Mail::assertSent(AccountPasswordResetMail::class, function (AccountPasswordResetMail $mail) {
        return $mail->hasTo('budi@example.test')
            && str_contains($mail->resetUrl, '/reset-password/');
    });
});

it('asks the user to attach an email before requesting a reset link', function () {
    Mail::fake();

    $referral = Referral::factory()->create(['email' => null]);
    $referral->user->update(['username' => 'tanpaemail']);

    Livewire::test(ForgotPassword::class)
        ->set('username', 'tanpaemail')
        ->call('submit')
        ->assertHasErrors('username')
        ->assertSee('belum memiliki alamat email');

    Mail::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $referral = Referral::factory()->create();
    $plainToken = 'token-reset-yang-valid';

    AccountPasswordReset::create([
        'user_id' => $referral->user_id,
        'email' => $referral->email,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(60),
    ]);

    Livewire::test(ResetPassword::class, ['token' => $plainToken])
        ->set('password', 'password-baru-aman')
        ->set('password_confirmation', 'password-baru-aman')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('password-baru-aman', $referral->user->fresh()->password))->toBeTrue()
        ->and(AccountPasswordReset::where('user_id', $referral->user_id)->exists())->toBeFalse();
});

it('lets a logged in referral request a reset email from profile', function () {
    Mail::fake();

    $referral = Referral::factory()->create(['email' => 'profile@example.test']);

    Livewire::actingAs($referral->user)
        ->test(ReferralProfile::class)
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors()
        ->assertSee('Link reset kata sandi sudah dikirim');

    Mail::assertSent(AccountPasswordResetMail::class, fn (AccountPasswordResetMail $mail) => $mail->hasTo('profile@example.test'));
});

it('lets admin save an email and request a reset link from the account profile', function () {
    Mail::fake();

    $admin = Admin::factory()->create(['email' => null]);

    Livewire::actingAs($admin->user)
        ->test(AdminProfile::class)
        ->call('edit')
        ->set('email', 'admin@example.test')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::actingAs($admin->user)
        ->test(AdminProfile::class)
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors()
        ->assertSee('Link reset kata sandi sudah dikirim');

    Mail::assertSent(AccountPasswordResetMail::class, fn (AccountPasswordResetMail $mail) => $mail->hasTo('admin@example.test'));
});
