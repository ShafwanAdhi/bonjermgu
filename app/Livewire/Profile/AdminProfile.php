<?php

namespace App\Livewire\Profile;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AdminProfile extends Component
{
    public bool $editing = false;

    public string $full_name = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->fillFromProfile();
    }

    #[Computed]
    public function profile(): Admin
    {
        return Admin::with('user')
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'full_name' => 'Nama Lengkap',
        ];
    }

    public function edit(): void
    {
        $this->fillFromProfile();
        $this->editing = true;
        $this->dispatch('profile-editing');
    }

    public function cancel(): void
    {
        $this->resetValidation();
        $this->fillFromProfile();
        $this->editing = false;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->profile->update([
            'full_name' => $validated['full_name'],
        ]);

        unset($this->profile);
        $this->editing = false;

        session()->flash('profile_success', 'Profil berhasil diperbarui.');
    }

    public function changePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi Kata Sandi tidak sama.',
        ], [
            'current_password' => 'Kata Sandi Saat Ini',
            'password' => 'Kata Sandi Baru',
            'password_confirmation' => 'Konfirmasi Kata Sandi Baru',
        ]);

        if (! Hash::check($validated['current_password'], Auth::user()->password)) {
            $this->addError('current_password', 'Kata Sandi Saat Ini tidak cocok.');

            return;
        }

        Auth::user()->update(['password' => $validated['password']]);

        $this->reset('current_password', 'password', 'password_confirmation');

        session()->flash('password_success', 'Kata sandi berhasil diperbarui.');
    }

    private function fillFromProfile(): void
    {
        $this->full_name = $this->profile->full_name;
    }

    public function render()
    {
        return view('livewire.profile.admin-profile');
    }
}
