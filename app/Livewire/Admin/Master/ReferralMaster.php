<?php

namespace App\Livewire\Admin\Master;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Services\ConfigurationIntegrityValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ReferralMaster extends AuditedAdminComponent
{
    public ?int $categoryId = null;

    public ?int $subCategoryId = null;

    public ?int $institutionId = null;

    public string $activeEditor = 'category';

    /** @var array<string, mixed> */
    public array $categoryForm = [];

    /** @var array<string, mixed> */
    public array $subCategoryForm = [];

    /** @var array<string, mixed> */
    public array $institutionForm = [];

    public function mount(): void
    {
        $this->newCategory();
        $this->newSubCategory();
        $this->newInstitution();
        $this->activeEditor = 'category';
    }

    public function newCategory(): void
    {
        $this->categoryId = null;
        $this->categoryForm = [
            'name' => '',
            'code' => '',
            'segment' => 'Reguler',
            'tier' => '',
            'allows_passenger' => true,
            'allows_commercial' => true,
            'is_active' => true,
        ];
        $this->resetValidation();
        $this->openEditor('category');
    }

    public function editCategory(int $categoryId): void
    {
        $category = ReferralCategory::query()->findOrFail($categoryId);
        $this->categoryId = $category->id;
        $this->categoryForm = [
            'name' => $category->name,
            'code' => $category->code,
            'segment' => $category->segment,
            'tier' => $category->tier,
            'allows_passenger' => $category->allows_passenger,
            'allows_commercial' => $category->allows_commercial,
            'is_active' => $category->is_active,
        ];
        $this->resetValidation();
        $this->openEditor('category');
    }

    public function saveCategory(ConfigurationIntegrityValidator $integrity): void
    {
        $validated = $this->validate([
            'categoryForm.name' => ['required', 'string', 'max:100', Rule::unique('referral_categories', 'name')->ignore($this->categoryId)],
            'categoryForm.code' => ['required', 'string', 'max:10', Rule::unique('referral_categories', 'code')->ignore($this->categoryId)],
            'categoryForm.segment' => ['required', Rule::in(['Reguler', 'Captive'])],
            // `Referral C2C` is retired: the client confirmed it is the same
            // category as `Referral`. A CHECK constraint rejects it too — this
            // rule is what turns that into a readable message.
            'categoryForm.tier' => ['required', 'string', 'max:100', Rule::notIn(['Referral C2C'])],
            'categoryForm.allows_passenger' => ['required', 'boolean'],
            'categoryForm.allows_commercial' => ['required', 'boolean'],
            'categoryForm.is_active' => ['required', 'boolean'],
        ], [
            'categoryForm.tier.not_in' => 'Tier "Referral C2C" sudah tidak digunakan. Gunakan "Referral".',
        ], [
            'categoryForm.name' => 'Nama Kategori',
            'categoryForm.code' => 'Kode Kategori',
            'categoryForm.segment' => 'Segment',
            'categoryForm.tier' => 'Tier',
        ]);

        if (! $validated['categoryForm']['allows_passenger'] && ! $validated['categoryForm']['allows_commercial']) {
            throw ValidationException::withMessages([
                'categoryForm.allows_passenger' => 'Sedikitnya satu penggunaan kendaraan harus diizinkan.',
            ]);
        }

        $categoryId = DB::transaction(function () use ($validated, $integrity): int {
            $category = $this->categoryId ? ReferralCategory::query()->findOrFail($this->categoryId) : new ReferralCategory;
            $category->fill([
                'name' => trim($validated['categoryForm']['name']),
                'code' => strtoupper(trim($validated['categoryForm']['code'])),
                'segment' => $validated['categoryForm']['segment'],
                'tier' => trim($validated['categoryForm']['tier']),
                'allows_passenger' => $validated['categoryForm']['allows_passenger'],
                'allows_commercial' => $validated['categoryForm']['allows_commercial'],
                'is_active' => $validated['categoryForm']['is_active'],
            ])->save();

            $integrity->assertProductCoverage();

            return $category->id;
        });

        $this->editCategory($categoryId);
        $this->openEditor('category');
        $this->refreshAudit();
        session()->flash('admin_success', 'Kategori Referral berhasil disimpan.');
    }

    public function deleteCategory(): void
    {
        $category = ReferralCategory::query()->with('subCategories.institutions')->findOrFail($this->categoryId);
        $accountCount = Referral::query()->where('category_id', $category->id)->count();

        if ($accountCount > 0) {
            throw ValidationException::withMessages([
                'master' => "Kategori dipakai {$accountCount} akun Referral dan tidak dapat dihapus.",
            ]);
        }

        DB::transaction(function () use ($category): void {
            foreach ($category->subCategories as $subCategory) {
                $subCategory->institutions->each->delete();
                $subCategory->delete();
            }

            $category->delete();
        });

        $this->newCategory();
        $this->openEditor('category');
        $this->refreshAudit();
        session()->flash('admin_success', 'Kategori beserta master turunannya berhasil dihapus.');
    }

    public function newSubCategory(?int $categoryId = null): void
    {
        $this->subCategoryId = null;
        $this->subCategoryForm = ['category_id' => $categoryId ?: '', 'name' => ''];
        $this->resetValidation();
        $this->openEditor('sub-category');
    }

    public function editSubCategory(int $subCategoryId): void
    {
        $sub = ReferralSubCategory::query()->findOrFail($subCategoryId);
        $this->subCategoryId = $sub->id;
        $this->subCategoryForm = ['category_id' => $sub->category_id, 'name' => $sub->name];
        $this->resetValidation();
        $this->openEditor('sub-category');
    }

    public function saveSubCategory(): void
    {
        $validated = $this->validate([
            'subCategoryForm.category_id' => ['required', 'integer', Rule::exists('referral_categories', 'id')],
            'subCategoryForm.name' => [
                'required', 'string', 'max:100',
                Rule::unique('referral_sub_categories', 'name')
                    ->where('category_id', $this->subCategoryForm['category_id'] ?: null)
                    ->ignore($this->subCategoryId),
            ],
        ], [], [
            'subCategoryForm.category_id' => 'Kategori',
            'subCategoryForm.name' => 'Nama Sub-kategori',
        ]);

        $sub = $this->subCategoryId ? ReferralSubCategory::query()->findOrFail($this->subCategoryId) : new ReferralSubCategory;
        $sub->fill([
            'category_id' => $validated['subCategoryForm']['category_id'],
            'name' => trim($validated['subCategoryForm']['name']),
        ])->save();

        $this->editSubCategory($sub->id);
        $this->openEditor('sub-category');
        $this->refreshAudit();
        session()->flash('admin_success', 'Sub-kategori Referral berhasil disimpan.');
    }

    public function deleteSubCategory(): void
    {
        $sub = ReferralSubCategory::query()->with('institutions')->findOrFail($this->subCategoryId);
        $institutionIds = $sub->institutions->pluck('id');
        $used = Referral::query()
            ->where('sub_category_id', $sub->id)
            ->orWhereIn('institution_id', $institutionIds)
            ->exists();

        if ($used) {
            throw ValidationException::withMessages(['master' => 'Sub-kategori atau instansinya masih dipakai akun Referral.']);
        }

        DB::transaction(function () use ($sub): void {
            $sub->institutions->each->delete();
            $sub->delete();
        });

        $this->newSubCategory();
        $this->openEditor('sub-category');
        $this->refreshAudit();
        session()->flash('admin_success', 'Sub-kategori beserta instansinya berhasil dihapus.');
    }

    public function newInstitution(?int $subCategoryId = null): void
    {
        $this->institutionId = null;
        $this->institutionForm = ['sub_category_id' => $subCategoryId ?: '', 'name' => ''];
        $this->resetValidation();
        $this->openEditor('institution');
    }

    public function editInstitution(int $institutionId): void
    {
        $institution = Institution::query()->findOrFail($institutionId);
        $this->institutionId = $institution->id;
        $this->institutionForm = [
            'sub_category_id' => $institution->sub_category_id,
            'name' => $institution->name,
        ];
        $this->resetValidation();
        $this->openEditor('institution');
    }

    public function saveInstitution(): void
    {
        $validated = $this->validate([
            'institutionForm.sub_category_id' => ['required', 'integer', Rule::exists('referral_sub_categories', 'id')],
            'institutionForm.name' => [
                'required', 'string', 'max:150',
                Rule::unique('institutions', 'name')
                    ->where('sub_category_id', $this->institutionForm['sub_category_id'] ?: null)
                    ->ignore($this->institutionId),
            ],
        ], [], [
            'institutionForm.sub_category_id' => 'Sub-kategori',
            'institutionForm.name' => 'Nama Instansi',
        ]);

        $institution = $this->institutionId ? Institution::query()->findOrFail($this->institutionId) : new Institution;
        $institution->fill([
            'sub_category_id' => $validated['institutionForm']['sub_category_id'],
            'name' => trim($validated['institutionForm']['name']),
        ])->save();

        $this->editInstitution($institution->id);
        $this->openEditor('institution');
        $this->refreshAudit();
        session()->flash('admin_success', 'Instansi Referral berhasil disimpan.');
    }

    public function deleteInstitution(): void
    {
        $institution = Institution::query()->findOrFail($this->institutionId);

        if (Referral::query()->where('institution_id', $institution->id)->exists()) {
            throw ValidationException::withMessages(['master' => 'Instansi masih dipakai akun Referral dan tidak dapat dihapus.']);
        }

        $institution->delete();
        $this->newInstitution();
        $this->openEditor('institution');
        $this->refreshAudit();
        session()->flash('admin_success', 'Instansi berhasil dihapus.');
    }

    public function render(): View
    {
        return view('admin.master.referral', [
            'categories' => ReferralCategory::query()
                ->with(['subCategories.institutions'])
                ->withCount('subCategories')
                ->orderBy('name')
                ->get()
                ->each(fn (ReferralCategory $category) => $category->setAttribute(
                    'accounts_count',
                    Referral::query()->where('category_id', $category->id)->count(),
                )),
            'subCategories' => ReferralSubCategory::query()->with('category')->orderBy('name')->get(),
        ])->layout('components.layouts.app', ['title' => 'Master Referral — Kebon Jeruk Multiguna']);
    }

    protected function auditTables(): array
    {
        return ['referral_categories', 'referral_sub_categories', 'institutions'];
    }

    protected function auditModule(): string
    {
        return 'master.referral';
    }

    private function openEditor(string $editor): void
    {
        $this->activeEditor = $editor;
        $this->dispatch('master-panel-opened');
    }
}
