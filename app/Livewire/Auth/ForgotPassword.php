<?php

namespace App\Livewire\Auth;

use App\Support\AccountPasswordResetBroker;
use App\Support\PasswordResetResult;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ForgotPassword extends Component
{
    public string $username = '';

    public bool $sent = false;

    public function submit(AccountPasswordResetBroker $broker): void
    {
        $this->validate([
            'username' => ['required', 'string', 'max:50'],
        ], [], [
            'username' => 'Nama User',
        ]);

        $this->ensureIsNotRateLimited();

        $result = $broker->sendForUsername($this->username);

        if ($result === PasswordResetResult::MissingEmail) {
            throw ValidationException::withMessages([
                'username' => 'Akun ini belum memiliki alamat email. Masuk lalu pasang email di Profil, atau minta Admin memasangkannya terlebih dahulu.',
            ]);
        }

        if ($result === PasswordResetResult::Inactive) {
            throw ValidationException::withMessages([
                'username' => 'Akun ini tidak aktif. Hubungi Admin.',
            ]);
        }

        RateLimiter::hit($this->throttleKey(), 60);
        $this->sent = true;
        $this->reset('username');
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "Tunggu {$seconds} detik sebelum meminta link baru.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|password-reset|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
