<?php

namespace App\Livewire\Admin\Master;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehiclePrice;
use App\Models\VehicleType;
use App\Models\VehicleUsage;
use App\Support\RupiahInput;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

final class Vehicles extends AuditedAdminComponent
{
    use WithPagination;

    public string $search = '';

    public ?int $usageId = null;

    public ?int $brandId = null;

    public ?int $typeId = null;

    public ?int $modelId = null;

    /** @var array<string, mixed> */
    public array $brandForm = ['name' => '', 'origin' => 'Japan'];

    /** @var array<string, mixed> */
    public array $typeForm = ['name' => ''];

    /** @var array<string, mixed> */
    public array $modelForm = ['name' => ''];

    /** @var array<int, array{id: int|null, year: string, price: string}> */
    public array $prices = [];

    public function mount(): void
    {
        $usageId = VehicleUsage::query()->orderBy('name')->value('id');

        if ($usageId) {
            $this->selectUsage((int) $usageId);
            $brandId = VehicleBrand::query()->where('usage_id', $usageId)->orderBy('name')->value('id');

            if ($brandId) {
                $this->selectBrand((int) $brandId);
                $typeId = VehicleType::query()->where('brand_id', $brandId)->orderBy('name')->value('id');

                if ($typeId) {
                    $this->selectType((int) $typeId);
                    $modelId = VehicleModel::query()->where('type_id', $typeId)->orderBy('name')->value('id');

                    if ($modelId) {
                        $this->selectModel((int) $modelId);
                    }
                }
            }
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function usages()
    {
        return VehicleUsage::query()->orderBy('name')->get();
    }

    #[Computed]
    public function brands()
    {
        return $this->usageId
            ? VehicleBrand::query()->where('usage_id', $this->usageId)->orderBy('name')->get()
            : collect();
    }

    #[Computed]
    public function types()
    {
        return $this->brandId
            ? VehicleType::query()->where('brand_id', $this->brandId)->orderBy('name')->get()
            : collect();
    }

    #[Computed]
    public function models()
    {
        return $this->typeId
            ? VehicleModel::query()->where('type_id', $this->typeId)->orderBy('name')->get()
            : collect();
    }

    public function selectUsage(int $usageId): void
    {
        VehicleUsage::query()->findOrFail($usageId);
        $this->usageId = $usageId;
        $this->newBrand();
        unset($this->brands, $this->types, $this->models);
    }

    public function selectBrand(int $brandId): void
    {
        $brand = VehicleBrand::query()->where('usage_id', $this->usageId)->findOrFail($brandId);
        $this->brandId = $brand->id;
        $this->brandForm = ['name' => $brand->name, 'origin' => $brand->origin];
        $this->newType();
        unset($this->types, $this->models);
    }

    public function selectType(int $typeId): void
    {
        $type = VehicleType::query()->where('brand_id', $this->brandId)->findOrFail($typeId);
        $this->typeId = $type->id;
        $this->typeForm = ['name' => $type->name];
        $this->newModel();
        unset($this->models);
    }

    public function selectModel(int $modelId): void
    {
        $model = VehicleModel::query()->where('type_id', $this->typeId)->findOrFail($modelId);
        $this->modelId = $model->id;
        $this->modelForm = ['name' => $model->name];
        $this->loadPrices();
    }

    public function selectSearchResult(int $modelId): void
    {
        $model = VehicleModel::query()->with('type.brand')->findOrFail($modelId);
        $this->selectUsage($model->type->brand->usage_id);
        $this->selectBrand($model->type->brand_id);
        $this->selectType($model->type_id);
        $this->selectModel($model->id);
    }

    public function newBrand(): void
    {
        $this->brandId = null;
        $this->brandForm = ['name' => '', 'origin' => 'Japan'];
        $this->newType();
        unset($this->types, $this->models);
    }

    public function newType(): void
    {
        $this->typeId = null;
        $this->typeForm = ['name' => ''];
        $this->newModel();
        unset($this->models);
    }

    public function newModel(): void
    {
        $this->modelId = null;
        $this->modelForm = ['name' => ''];
        $this->prices = [];
    }

    public function saveBrand(): void
    {
        $validated = $this->validate([
            'usageId' => ['required', 'integer', Rule::exists('vehicle_usages', 'id')],
            'brandForm.name' => [
                'required', 'string', 'max:100',
                Rule::unique('vehicle_brands', 'name')->where('usage_id', $this->usageId)->ignore($this->brandId),
            ],
            'brandForm.origin' => ['required', Rule::in(['Japan', 'Non Japan'])],
        ], [], ['brandForm.name' => 'Merk', 'brandForm.origin' => 'Klasifikasi Asal']);

        $brand = $this->brandId ? VehicleBrand::query()->findOrFail($this->brandId) : new VehicleBrand;
        $brand->fill([
            'usage_id' => $validated['usageId'],
            'name' => trim($validated['brandForm']['name']),
            'origin' => $validated['brandForm']['origin'],
        ])->save();

        $this->selectBrand($brand->id);
        $this->refreshAudit();
        session()->flash('admin_success', 'Merk kendaraan berhasil disimpan.');
    }

    public function deleteBrand(): void
    {
        $brand = VehicleBrand::query()->withCount('types')->findOrFail($this->brandId);

        if ($brand->types_count > 0) {
            throw ValidationException::withMessages(['master' => 'Merk masih mempunyai Type dan tidak dapat dihapus.']);
        }

        $brand->delete();
        $this->newBrand();
        unset($this->brands);
        $this->refreshAudit();
        session()->flash('admin_success', 'Merk kendaraan berhasil dihapus.');
    }

    public function saveType(): void
    {
        $validated = $this->validate([
            'brandId' => ['required', 'integer', Rule::exists('vehicle_brands', 'id')->where('usage_id', $this->usageId)],
            'typeForm.name' => [
                'required', 'string', 'max:100',
                Rule::unique('vehicle_types', 'name')->where('brand_id', $this->brandId)->ignore($this->typeId),
            ],
        ], [], ['typeForm.name' => 'Type Kendaraan']);

        $type = $this->typeId ? VehicleType::query()->findOrFail($this->typeId) : new VehicleType;
        $type->fill(['brand_id' => $validated['brandId'], 'name' => trim($validated['typeForm']['name'])])->save();
        $this->selectType($type->id);
        $this->refreshAudit();
        session()->flash('admin_success', 'Type kendaraan berhasil disimpan.');
    }

    public function deleteType(): void
    {
        $type = VehicleType::query()->withCount('models')->findOrFail($this->typeId);

        if ($type->models_count > 0) {
            throw ValidationException::withMessages(['master' => 'Type masih mempunyai Model dan tidak dapat dihapus.']);
        }

        $type->delete();
        $this->newType();
        unset($this->types);
        $this->refreshAudit();
        session()->flash('admin_success', 'Type kendaraan berhasil dihapus.');
    }

    public function saveModel(): void
    {
        $validated = $this->validate([
            'typeId' => ['required', 'integer', Rule::exists('vehicle_types', 'id')->where('brand_id', $this->brandId)],
            'modelForm.name' => [
                'required', 'string', 'max:150',
                Rule::unique('vehicle_models', 'name')->where('type_id', $this->typeId)->ignore($this->modelId),
            ],
        ], [], ['modelForm.name' => 'Model Kendaraan']);

        $model = $this->modelId ? VehicleModel::query()->findOrFail($this->modelId) : new VehicleModel;
        $model->fill(['type_id' => $validated['typeId'], 'name' => trim($validated['modelForm']['name'])])->save();
        $this->selectModel($model->id);
        $this->refreshAudit();
        session()->flash('admin_success', 'Model kendaraan berhasil disimpan.');
    }

    public function deleteModel(): void
    {
        $model = VehicleModel::query()->withCount('prices')->findOrFail($this->modelId);

        if ($model->prices_count > 0) {
            throw ValidationException::withMessages(['master' => 'Model masih mempunyai harga PHPM. Hapus seluruh harga terlebih dahulu.']);
        }

        $model->delete();
        $this->newModel();
        unset($this->models);
        $this->refreshAudit();
        session()->flash('admin_success', 'Model kendaraan berhasil dihapus.');
    }

    public function addPrice(): void
    {
        $this->prices[] = ['id' => null, 'year' => '', 'price' => ''];
    }

    public function removePrice(int $index): void
    {
        unset($this->prices[$index]);
        $this->prices = array_values($this->prices);
    }

    public function savePrices(): void
    {
        $this->prices = RupiahInput::normalizeRows($this->prices, ['price']);

        $validated = $this->validate([
            'modelId' => ['required', 'integer', Rule::exists('vehicle_models', 'id')->where('type_id', $this->typeId)],
            'prices' => ['array'],
            'prices.*.id' => ['nullable', 'integer'],
            'prices.*.year' => ['required', 'integer', 'between:1,32767', 'distinct'],
            'prices.*.price' => ['required', 'integer', 'min:0'],
        ], [], ['prices.*.year' => 'Tahun Kendaraan', 'prices.*.price' => 'Harga PHPM']);

        DB::transaction(function () use ($validated): void {
            $ids = collect($validated['prices'])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            VehiclePrice::query()
                ->where('model_id', $validated['modelId'])
                ->when($ids !== [], fn ($query) => $query->whereNotIn('id', $ids))
                ->get()
                ->each
                ->delete();

            foreach ($validated['prices'] as $row) {
                $price = $row['id'] ? VehiclePrice::query()->findOrFail($row['id']) : new VehiclePrice;
                $price->fill([
                    'model_id' => $validated['modelId'],
                    'year' => $row['year'],
                    'price' => $row['price'],
                ])->save();
            }
        });

        $this->loadPrices();
        $this->refreshAudit();
        session()->flash('admin_success', 'Harga PHPM per tahun berhasil disimpan.');
    }

    public function render(): View
    {
        $searchResults = null;

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $searchResults = VehicleModel::query()
                ->with('type.brand.usage')
                ->where(function ($query) use ($term): void {
                    $query->where('vehicle_models.name', 'ilike', $term)
                        ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'ilike', $term))
                        ->orWhereHas('type.brand', fn ($brandQuery) => $brandQuery->where('name', 'ilike', $term));
                })
                ->orderBy('vehicle_models.name')
                ->paginate(20);
        }

        return view('admin.master.vehicles', compact('searchResults'))
            ->layout('components.layouts.app', ['title' => 'Master Kendaraan — Kebon Jeruk Multiguna']);
    }

    private function loadPrices(): void
    {
        $this->prices = VehiclePrice::query()->where('model_id', $this->modelId)->orderByDesc('year')->get()
            ->map(fn (VehiclePrice $row) => [
                'id' => $row->id,
                'year' => (string) $row->year,
                'price' => (string) $row->price,
            ])->all();
        $this->resetValidation();
    }

    protected function auditTables(): array
    {
        return ['vehicle_usages', 'vehicle_brands', 'vehicle_types', 'vehicle_models', 'vehicle_prices'];
    }
}
