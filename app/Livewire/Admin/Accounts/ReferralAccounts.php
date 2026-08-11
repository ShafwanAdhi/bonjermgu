<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin view and edit of Referral profiles — docs/pages.md section 14.
 *
 * Profiles only. There is deliberately no path from here to a Referral's
 * applications: the global scope hands Admin an empty set (AD-09).
 */
#[Layout('components.layouts.app')]
class ReferralAccounts extends Component
{
    use WithPagination;

    public string $pageMode = 'list';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $editingId = null;

    public string $full_name = '';

    public string $birth_date = '';

    public string $email = '';

    public string $phone = '';

    public ?string $category_id = null;

    public ?string $sub_category_id = null;

    public ?string $institution_id = null;

    public string $branch_name = '';

    public bool $is_active = true;

    public function mount(?Referral $referral = null): void
    {
        if ($referral?->exists) {
            $this->pageMode = 'edit';
            $this->edit($referral->id);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function accounts(): LengthAwarePaginator
    {
        return Referral::query()
            ->with(['user:id,username,is_active', 'category:id,name', 'subCategory:id,name', 'institution:id,name'])
            ->when($this->search !== '', function ($query) {
                $term = '%'.str_replace('%', '\%', $this->search).'%';

                $query->where(fn ($q) => $q
                    ->where('full_name', 'ilike', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('username', 'ilike', $term)));
            })
            ->orderBy('full_name')
            ->paginate(15);
    }

    #[Computed]
    public function categories(): Collection
    {
        return ReferralCategory::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function subCategories(): Collection
    {
        return $this->category_id
            ? ReferralSubCategory::where('category_id', $this->category_id)->orderBy('name')->get(['id', 'name'])
            : collect();
    }

    #[Computed]
    public function institutions(): Collection
    {
        return $this->sub_category_id
            ? Institution::where('sub_category_id', $this->sub_category_id)->orderBy('name')->get(['id', 'name'])
            : collect();
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
            'is_active' => ['boolean'],
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

    protected function messages(): array
    {
        return [
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

    public function edit(int $id): void
    {
        $referral = Referral::with('user')->findOrFail($id);

        $this->editingId = $referral->id;
        $this->full_name = $referral->full_name;
        $this->birth_date = $referral->birth_date?->format('Y-m-d') ?? '';
        $this->email = $referral->email ?? '';
        $this->phone = $referral->phone ?? '';
        $this->category_id = (string) $referral->category_id;
        $this->sub_category_id = (string) $referral->sub_category_id;
        $this->institution_id = $referral->institution_id ? (string) $referral->institution_id : null;
        $this->branch_name = $referral->branch_name ?? '';
        $this->is_active = (bool) $referral->user->is_active;

        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset([
            'editingId', 'full_name', 'birth_date', 'email', 'phone',
            'category_id', 'sub_category_id', 'institution_id', 'branch_name', 'is_active',
        ]);
        $this->resetValidation();

        if ($this->pageMode === 'edit') {
            $this->redirectRoute('accounts.referrals', navigate: true);
        }
    }

    public function save(): void
    {
        $validated = $this->validate();
        $isEditPage = $this->pageMode === 'edit';

        $referral = Referral::with('user')->findOrFail($this->editingId);

        $referral->update([
            'full_name' => $validated['full_name'],
            'birth_date' => $validated['birth_date'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'category_id' => $validated['category_id'],
            'sub_category_id' => $validated['sub_category_id'],
            'institution_id' => $validated['institution_id'] ?: null,
            'branch_name' => $validated['branch_name'] ?: null,
        ]);

        // Deactivating blocks login; it does not touch the stored password.
        $referral->user->update(['is_active' => $validated['is_active']]);

        unset($this->accounts);
        $this->reset([
            'editingId', 'full_name', 'birth_date', 'email', 'phone',
            'category_id', 'sub_category_id', 'institution_id', 'branch_name', 'is_active',
        ]);
        $this->resetValidation();

        session()->flash('account_success', 'Profil Referral berhasil diperbarui.');

        if ($isEditPage) {
            $this->redirectRoute('accounts.referrals', navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.admin.accounts.referral-accounts');
    }
}
