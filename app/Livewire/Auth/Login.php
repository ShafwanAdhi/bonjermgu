<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'username' => 'Nama User',
            'password' => 'Kata Sandi',
            'remember' => 'Ingat Saya',
        ];
    }

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $credentials = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        if (! Auth::attempt($credentials, remember: $this->remember)) {
            RateLimiter::hit($this->throttleKey(), config('account.login.decay_seconds'));

            // Username and IP only. The submitted password never reaches a log.
            Log::warning('Failed login attempt', [
                'username' => $this->username,
                'ip' => request()->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Nama User atau Kata Sandi tidak cocok.',
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            session()->invalidate();

            throw ValidationException::withMessages([
                'username' => 'Akun ini tidak aktif. Hubungi Admin.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Session fixation defence — mandatory, per AD-15.
        session()->regenerate();

        $this->redirectIntended(route(Auth::user()->role->homeRoute()), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        $maxAttempts = config('account.login.max_attempts');

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /** Throttled per username and IP together, not per username alone. */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
