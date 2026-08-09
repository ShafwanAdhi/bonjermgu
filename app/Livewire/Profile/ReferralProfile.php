<?php

namespace App\Livewire\Profile;

use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * A Referral's own profile.
 */
class ReferralProfile extends Component
{
    public bool $editing = false;

    public string $full_name = '';

    public string $birth_date = '';

    public string $email = '';

    public string $phone = '';

    public ?string $category_id = null;

    public ?string $sub_category_id = null;

    public ?string $institution_id = null;

    public string $branch_name = '';

    public function mount(): void
    {
        $this->fillFromProfile();
    }

    #[Computed]
    public function profile(): Referral
    {
        return Referral::with(['category', 'subCategory', 'institution'])
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    #[Computed]
    public function categories(): Collection
    {
        return ReferralCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function subCategories(): Collection
    {
        if (! $this->category_id) {
            return collect();
        }

        return ReferralSubCategory::query()
            ->where('category_id', $this->category_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function institutions(): Collection
    {
        if (! $this->sub_category_id) {
            return collect();
        }

        return Institution::query()
            ->where('sub_category_id', $this->sub_category_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => ['nullable', 'email:rfc,strict', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'category_id' => ['required', Rule::exists('referral_categories', 'id')],
            'sub_category_id' => [
                'required',
                Rule::exists('referral_sub_categories', 'id')->where('category_id', $this->category_id),
            ],
            'institution_id' => [
                $this->institutions->isEmpty() ? 'nullable' : 'required',
                Rule::exists('institutions', 'id')->where('sub_category_id', $this->sub_category_id),
            ],
            'branch_name' => ['nullable', 'string', 'max:150'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'full_name' => 'Nama Lengkap',
            'birth_date' => 'Tanggal Lahir',
            'email' => 'Alamat Email',
            'phone' => 'No. Handphone',
            'category_id' => 'Kategori',
            'sub_category_id' => 'Sub-kategori',
            'institution_id' => 'Instansi',
            'branch_name' => 'Nama Cabang',
        ];
    }

    public function updatedCategoryId(): void
    {
        $this->reset('sub_category_id', 'institution_id');
        unset($this->subCategories, $this->institutions);
    }

    public function updatedSubCategoryId(): void
    {
        $this->reset('institution_id');
        unset($this->institutions);
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
            'category_id' => $validated['category_id'],
            'sub_category_id' => $validated['sub_category_id'],
            'institution_id' => $validated['institution_id'] ?: null,
            'branch_name' => $validated['branch_name'] ?: null,
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
        $this->category_id = (string) $profile->category_id;
        $this->sub_category_id = (string) $profile->sub_category_id;
        $this->institution_id = $profile->institution_id ? (string) $profile->institution_id : null;
        $this->branch_name = $profile->branch_name ?? '';
    }

    public function render()
    {
        return view('livewire.profile.referral-profile');
    }
}
