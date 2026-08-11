<?php

namespace App\Livewire\Admin;

use App\Domain\Application\FinancingProduct;
use App\Domain\Lending\LendingFilters;
use App\Domain\Lending\LendingQuery;
use App\Domain\Lending\LendingRow;
use App\Models\ReferralCategory;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class LendingReport extends Component
{
    public string $tab = 'ao';

    #[Url(as: 'month', except: '')]
    public string $month = '';

    #[Url(as: 'product', except: '')]
    public string $product = '';

    #[Url(as: 'category_id', except: '')]
    public string $category_id = '';

    public function mount(string $tab = 'ao'): void
    {
        $this->tab = $tab === 'referral' ? 'referral' : 'ao';
    }

    #[Computed]
    public function filters(): LendingFilters
    {
        return LendingFilters::fromArray([
            'month' => $this->month,
            'product' => $this->product,
            'category_id' => $this->category_id,
        ]);
    }

    #[Computed]
    public function rows(): Collection
    {
        return $this->tab === 'ao'
            ? LendingQuery::perOfficer($this->filters)
            : LendingQuery::perReferral($this->filters);
    }

    #[Computed]
    public function totals(): LendingRow
    {
        return LendingQuery::totals($this->filters);
    }

    #[Computed]
    public function categories(): Collection
    {
        return ReferralCategory::orderBy('name')->get(['id', 'name']);
    }

    public function products(): array
    {
        return FinancingProduct::cases();
    }

    public function clearFilters(): void
    {
        $this->reset('month', 'product', 'category_id');
    }

    public function render()
    {
        return view('livewire.admin.lending-report', [
            'nameHeading' => $this->tab === 'ao' ? 'Referral' : 'Account Officer',
            'reportTitle' => $this->tab === 'ao' ? 'Lending Per AO' : 'Lending Per Referral',
        ]);
    }
}
