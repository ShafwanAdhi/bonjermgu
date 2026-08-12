<?php

namespace App\Livewire\Auth;

use App\Support\AccountPasswordResetBroker;
use App\Support\PasswordResetResult;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ResetPassword extends Component
{
    public string $token = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function submit(AccountPasswordResetBroker $broker): void
    {
        $validated = $this->validate([
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi Kata Sandi tidak sama.',
        ], [
            'password' => 'Kata Sandi Baru',
            'password_confirmation' => 'Konfirmasi Kata Sandi Baru',
        ]);

        $result = $broker->reset($this->token, $validated['password']);

        if ($result === PasswordResetResult::Inactive) {
            throw ValidationException::withMessages([
                'password' => 'Akun ini tidak aktif. Hubungi Admin.',
            ]);
        }

        if ($result !== PasswordResetResult::Sent) {
            throw ValidationException::withMessages([
                'password' => 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru dari halaman lupa kata sandi.',
            ]);
        }

        session()->flash('reset_success', 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru.');

        $this->redirectRoute('login');
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
