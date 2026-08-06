<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Self-service Referral registration. The account is active immediately —
 * no Admin approval, per docs/actors.md section 8.
 *
 * The cascading selects are resolved server-side on every step. The full
 * institution list is never shipped to the client (docs/pages.md section 5).
 */
#[Layout('components.layouts.public')]
class Register extends Component
{
    public string $full_name = '';

    public string $birth_date = '';

    public string $email = '';

    public string $phone = '';

    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $category_id = null;

    public ?string $sub_category_id = null;

    public ?string $institution_id = null;

    public string $branch_name = '';

    public bool $registered = false;

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            // `email:rfc,strict` rather than the bare `email` rule: the default
            // rule accepts addresses containing CR/LF (GHSA-5vg9-5847-vvmq),
            // which has no fix on the 11.x line.
            'email' => ['nullable', 'email:rfc,strict', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'username' => ['required', 'string', 'min:4', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'category_id' => ['required', Rule::exists('referral_categories', 'id')],
            'sub_category_id' => [
                'required',
                Rule::exists('referral_sub_categories', 'id')
                    ->where('category_id', $this->category_id),
            ],
            'institution_id' => [
                // Empty is legitimate: not every sub-category has institutions.
                $this->institutions->isEmpty() ? 'nullable' : 'required',
                Rule::exists('institutions', 'id')
                    ->where('sub_category_id', $this->sub_category_id),
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
            'username' => 'Nama User',
            'password' => 'Kata Sandi',
            'password_confirmation' => 'Konfirmasi Kata Sandi',
            'category_id' => 'Kategori',
            'sub_category_id' => 'Sub-kategori',
            'institution_id' => 'Instansi',
            'branch_name' => 'Nama Cabang',
        ];
    }

    protected function messages(): array
    {
        return [
            'username.unique' => 'Nama User ini sudah dipakai. Pilih yang lain.',
            'username.alpha_dash' => 'Nama User hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'password.confirmed' => 'Konfirmasi Kata Sandi tidak sama.',
            'birth_date.before' => 'Tanggal Lahir harus sebelum hari ini.',
        ];
    }

    #[Computed]
    public function categories(): Collection
    {
        return $this->sortOptionsWithOthersLast(
            ReferralCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    #[Computed]
    public function subCategories(): Collection
    {
        if (! $this->category_id) {
            return collect();
        }

        return $this->sortOptionsWithOthersLast(
            ReferralSubCategory::query()
                ->where('category_id', $this->category_id)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    #[Computed]
    public function institutions(): Collection
    {
        if (! $this->sub_category_id) {
            return collect();
        }

        return $this->sortOptionsWithOthersLast(
            Institution::query()
                ->where('sub_category_id', $this->sub_category_id)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function categoryOptionLabel(string $name): string
    {
        return $name === 'Karyawan Internal & Captive'
            ? 'Karyawan Internal'
            : $name;
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

    public function register(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                // The `hashed` cast stores bcrypt. Plaintext is never persisted.
                'password' => $validated['password'],
                'role' => Role::Referral,
                'is_active' => true,
            ]);

            Referral::create([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'birth_date' => $validated['birth_date'],
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'category_id' => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'],
                'institution_id' => $validated['institution_id'] ?: null,
                'branch_name' => $validated['branch_name'] ?: null,
            ]);
        });

        $this->reset('password', 'password_confirmation');
        $this->registered = true;
    }

    public function render()
    {
        return view('livewire.auth.register');
    }

    private function sortOptionsWithOthersLast(Collection $options): Collection
    {
        return $options
            ->sortBy(fn ($option) => sprintf(
                '%d-%s',
                $this->isOthersLabel($option->name) ? 1 : 0,
                Str::lower($option->name),
            ))
            ->values();
    }

    private function isOthersLabel(string $name): bool
    {
        return in_array(Str::lower($name), ['others', 'other', 'lainnya'], true);
    }
}
