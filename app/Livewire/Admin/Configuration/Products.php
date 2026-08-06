<?php

namespace App\Livewire\Admin\Configuration;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\Product;
use App\Services\ConfigurationIntegrityValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class Products extends AuditedAdminComponent
{
    private const TENORS = [12, 24, 36, 48, 60];

    public ?int $productId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(): void
    {
        $firstId = Product::query()->orderBy('name')->value('id');

        $firstId ? $this->edit((int) $firstId) : $this->createProduct();
    }

    public function createProduct(): void
    {
        $this->productId = null;
        $this->form = [
            'name' => '',
            'rates' => array_fill_keys(self::TENORS, ''),
            'dp_rate' => '',
            'admin_min' => '',
            'admin_max' => '',
            'provisi_rate' => '0',
            'up_acp' => '0',
            'up_rate' => '0',
            'up_admin' => '0',
            'up_provisi' => '0',
            'is_active' => true,
        ];
        $this->resetValidation();
    }

    public function edit(int $productId): void
    {
        $product = Product::query()->with('rates')->findOrFail($productId);
        $rates = array_fill_keys(self::TENORS, '');

        foreach ($product->rates as $rate) {
            $rates[$rate->tenor_months] = $rate->effective_rate === null
                ? ''
                : $this->percent($rate->effective_rate);
        }

        $this->productId = $product->id;
        $this->form = [
            'name' => $product->name,
            'rates' => $rates,
            'dp_rate' => $this->percent($product->dp_rate),
            'admin_min' => (string) $product->admin_min,
            'admin_max' => (string) $product->admin_max,
            'provisi_rate' => $this->percent($product->provisi_rate),
            'up_acp' => $this->percent($product->up_acp),
            'up_rate' => $this->percent($product->up_rate),
            'up_admin' => (string) $product->up_admin,
            'up_provisi' => $this->percent($product->up_provisi),
            'is_active' => $product->is_active,
        ];
        $this->resetValidation();
    }

    public function save(ConfigurationIntegrityValidator $integrity): void
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        if (collect($validated['form']['rates'])->every(fn ($rate) => $rate === null || $rate === '')) {
            throw ValidationException::withMessages([
                'form.rates.12' => 'Sedikitnya satu tenor harus mempunyai effective rate.',
            ]);
        }

        $productId = DB::transaction(function () use ($validated, $integrity): int {
            $values = $validated['form'];
            $product = $this->productId
                ? Product::query()->findOrFail($this->productId)
                : new Product;

            $product->fill([
                'name' => $values['name'],
                'dp_rate' => $this->fraction($values['dp_rate']),
                'admin_min' => $values['admin_min'],
                'admin_max' => $values['admin_max'],
                'provisi_rate' => $this->fraction($values['provisi_rate']),
                'up_acp' => $this->fraction($values['up_acp']),
                'up_rate' => $this->fraction($values['up_rate']),
                'up_admin' => $values['up_admin'],
                'up_provisi' => $this->fraction($values['up_provisi']),
                'is_active' => $values['is_active'],
            ])->save();

            foreach (self::TENORS as $tenor) {
                $rate = $values['rates'][$tenor];
                $product->rates()->updateOrCreate(
                    ['tenor_months' => $tenor],
                    ['effective_rate' => $rate === null || $rate === '' ? null : $this->fraction($rate)],
                );
            }

            $integrity->assertProducts();

            return $product->id;
        });

        $this->edit($productId);
        $this->refreshAudit();
        session()->flash('admin_success', 'Product dan upping berhasil disimpan. Simulasi berikutnya memakai nilai baru.');
    }

    public function deleteProduct(ConfigurationIntegrityValidator $integrity): void
    {
        $product = Product::query()->findOrFail($this->productId);

        if ($product->is_active) {
            throw ValidationException::withMessages([
                'configuration' => 'Product aktif tidak dapat dihapus. Nonaktifkan dan simpan terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($product, $integrity): void {
            $product->rates()->get()->each->delete();
            $product->delete();
            $integrity->assertProducts();
        });

        $nextId = Product::query()->orderBy('name')->value('id');
        $nextId ? $this->edit((int) $nextId) : $this->createProduct();
        $this->refreshAudit();
        session()->flash('admin_success', 'Product nonaktif berhasil dihapus.');
    }

    public function render(): View
    {
        return view('admin.configuration.products', [
            // Rates eager-loaded so the list can show which tenors are
            // available without an N+1 — see the empty-vs-zero note in the view.
            'products' => Product::query()->with('rates')->orderBy('name')->get(),
            'tenors' => self::TENORS,
        ])->layout('components.layouts.app', ['title' => 'Product dan Upping — Kebon Jeruk Multiguna']);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        $rules = [
            'form.name' => ['required', 'string', 'max:150', Rule::unique('products', 'name')->ignore($this->productId)],
            'form.dp_rate' => ['required', 'numeric', 'between:0,100'],
            'form.admin_min' => ['required', 'integer', 'min:0', 'lte:form.admin_max'],
            'form.admin_max' => ['required', 'integer', 'min:0', 'gte:form.admin_min'],
            'form.provisi_rate' => ['required', 'numeric', 'between:0,100'],
            'form.up_acp' => ['required', 'numeric', 'between:0,100'],
            'form.up_rate' => ['required', 'numeric', 'between:0,100'],
            'form.up_admin' => ['required', 'integer', 'min:0'],
            'form.up_provisi' => ['required', 'numeric', 'between:0,100'],
            'form.is_active' => ['required', 'boolean'],
        ];

        foreach (self::TENORS as $tenor) {
            $rules["form.rates.{$tenor}"] = ['nullable', 'numeric', 'between:0,100'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function validationAttributes(): array
    {
        return [
            'form.name' => 'Nama Product',
            'form.dp_rate' => 'DP',
            'form.admin_min' => 'Admin Minimal',
            'form.admin_max' => 'Admin Maksimal',
            'form.provisi_rate' => 'Provisi',
            'form.up_acp' => 'Up ACP',
            'form.up_rate' => 'Up Rate',
            'form.up_admin' => 'Up Admin',
            'form.up_provisi' => 'Up Provisi',
        ];
    }

    protected function auditTables(): array
    {
        return ['products', 'product_rates'];
    }

    private function fraction(string|int|float $percent): float
    {
        return (float) $percent / 100;
    }

    private function percent(float $fraction): string
    {
        return rtrim(rtrim(number_format($fraction * 100, 6, '.', ''), '0'), '.');
    }
}
