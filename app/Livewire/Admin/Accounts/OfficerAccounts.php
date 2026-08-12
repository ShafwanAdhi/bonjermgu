<?php

namespace App\Livewire\Admin\Accounts;

use App\Enums\Role;
use App\Models\AccountOfficer;
use App\Models\User;
use App\Support\InitialPassword;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin creates and edits AO accounts — docs/actors.md section 8. An AO cannot
 * register itself.
 *
 * The initial password is generated and shown exactly once, on the response
 * to creation. It is never stored in readable form and there is no
 * screen that can show it again (docs/pages.md section 19, open item 2).
 */
#[Layout('components.layouts.app')]
class OfficerAccounts extends Component
{
    use WithPagination;

    public string $pageMode = 'list';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public bool $creating = false;

    public ?int $editingId = null;

    public string $full_name = '';

    public string $birth_date = '';

    public string $email = '';

    public string $phone = '';

    public string $username = '';

    public bool $is_active = true;

    /**
     * Held for this response only, so the Admin can hand it over. Livewire
     * clears it on the next interaction and it is never persisted.
     */
    public ?string $initialPassword = null;

    public ?string $createdName = null;

    public function mount(?AccountOfficer $officer = null): void
    {
        if (request()->routeIs('accounts.officers.create')) {
            $this->pageMode = 'create';
            $this->create();

            return;
        }

        if ($officer?->exists) {
            $this->pageMode = 'edit';
            $this->edit($officer->id);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function accounts(): LengthAwarePaginator
    {
        return AccountOfficer::query()
            ->with('user:id,username,is_active')
            ->when($this->search !== '', function ($query) {
                $term = '%'.str_replace('%', '\%', $this->search).'%';

                $query->where(fn ($q) => $q
                    ->where('full_name', 'ilike', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('username', 'ilike', $term)));
            })
            ->orderBy('full_name')
            ->paginate(15);
    }

    protected function rules(): array
    {
        $userId = $this->editingId
            ? AccountOfficer::whereKey($this->editingId)->value('user_id')
            : null;

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => ['nullable', 'email:rfc,strict', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            // Dots are allowed on purpose: AO usernames follow firstname.lastname.
            // `alpha_dash` would reject that.
            'username' => [
                'required', 'string', 'min:4', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
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
            'username' => 'Nama User',
        ];
    }

    protected function messages(): array
    {
        return [
            'username.unique' => 'Nama User ini sudah dipakai. Pilih yang lain.',
            'username.regex' => 'Nama User hanya boleh berisi huruf, angka, titik, strip, dan garis bawah.',
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->creating = true;
    }

    public function edit(int $id): void
    {
        $officer = AccountOfficer::with('user')->findOrFail($id);

        $this->resetForm();
        $this->editingId = $officer->id;
        $this->full_name = $officer->full_name;
        $this->birth_date = $officer->birth_date?->format('Y-m-d') ?? '';
        $this->email = $officer->email ?? '';
        $this->phone = $officer->phone ?? '';
        $this->username = $officer->user->username;
        $this->is_active = (bool) $officer->user->is_active;
    }

    public function cancel(): void
    {
        $this->resetForm();

        if ($this->pageMode !== 'list') {
            $this->redirectRoute('accounts.officers', navigate: true);
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->editingId
            ? $this->updateExisting($validated)
            : $this->createNew($validated);

        unset($this->accounts);
    }

    public function dismissInitialPassword(): void
    {
        $this->reset('initialPassword', 'createdName');
    }

    private function createNew(array $validated): void
    {
        $plaintext = InitialPassword::generate();

        DB::transaction(function () use ($validated, $plaintext) {
            $user = User::create([
                'username' => $validated['username'],
                // The `hashed` cast stores bcrypt; the plaintext is not saved.
                'password' => $plaintext,
                'role' => Role::AccountOfficer,
                'is_active' => $validated['is_active'],
            ]);

            AccountOfficer::create([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'birth_date' => $validated['birth_date'],
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
            ]);
        });

        $name = $validated['full_name'];
        $this->resetForm();

        // Survives resetForm on purpose — this is the one showing.
        $this->initialPassword = $plaintext;
        $this->createdName = $name;
    }

    private function updateExisting(array $validated): void
    {
        $officer = AccountOfficer::with('user')->findOrFail($this->editingId);

        DB::transaction(function () use ($officer, $validated) {
            $officer->update([
                'full_name' => $validated['full_name'],
                'birth_date' => $validated['birth_date'],
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
            ]);

            $officer->user->update([
                'username' => $validated['username'],
                'is_active' => $validated['is_active'],
            ]);
        });

        $this->resetForm();

        session()->flash('account_success', 'Akun AO berhasil diperbarui.');

        if ($this->pageMode === 'edit') {
            $this->redirectRoute('accounts.officers', navigate: true);
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'creating', 'editingId', 'full_name', 'birth_date',
            'email', 'phone', 'username', 'is_active', 'initialPassword', 'createdName',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.accounts.officer-accounts');
    }
}
