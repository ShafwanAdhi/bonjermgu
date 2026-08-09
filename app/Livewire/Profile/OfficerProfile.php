<?php

namespace App\Livewire\Profile;

use App\Models\AccountOfficer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * An AO's own profile.
 *
 * No category, sub-category, institution, or branch — those belong to Referral
 * alone (docs/actors.md section 7). Birth date is Admin-editable only,
 * matching the Referral rule.
 */
class OfficerProfile extends Component
{
    public bool $editing = false;

    public string $full_name = '';

    public string $birth_date = '';

    public string $email = '';

    public string $phone = '';

    public function mount(): void
    {
        $this->fillFromProfile();
    }

    #[Computed]
    public function profile(): AccountOfficer
    {
        return AccountOfficer::where('user_id', Auth::id())->firstOrFail();
    }

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => ['nullable', 'email:rfc,strict', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'full_name' => 'Nama Lengkap',
            'birth_date' => 'Tanggal Lahir',
            'email' => 'Alamat Email',
            'phone' => 'No. Handphone',
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
            'birth_date' => $validated['birth_date'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
        ]);

        unset($this->profile);
        $this->editing = false;

        session()->flash('profile_success', 'Profil berhasil diperbarui.');
    }

    private function fillFromProfile(): void
    {
        $profile = $this->profile;

        $this->full_name = $profile->full_name;
        $this->birth_date = $profile->birth_date?->format('Y-m-d') ?? '';
        $this->email = $profile->email ?? '';
        $this->phone = $profile->phone ?? '';
    }

    public function render()
    {
        return view('livewire.profile.officer-profile');
    }
}
