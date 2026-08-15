<?php

namespace App\Livewire\Application;

use App\Domain\Application\ApplicationStatus;
use App\Domain\Application\DocumentStatus;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\TrackingStatus;
use App\Enums\Role;
use App\Models\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar Aplikasi — docs/pages.md section 9. One page, two column sets.
 *
 * The row set is limited by the global scope on the model, never by the
 * filters below. A Referral sees what it brought in and an AO sees what it
 * owns because of ApplicationVisibilityScope, not because of anything here
 * (AD-09).
 */
#[Layout('components.layouts.app')]
class ApplicationList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'produk', except: '')]
    public string $product = '';

    #[Url(as: 'status', except: '')]
    public string $goLive = '';

    #[Url(as: 'bulan', except: '')]
    public string $month = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Application::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProduct(): void
    {
        $this->resetPage();
    }

    public function updatedGoLive(): void
    {
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function isOfficer(): bool
    {
        return Auth::user()->role === Role::AccountOfficer;
    }

    #[Computed]
    public function applications(): LengthAwarePaginator
    {
        $monthRange = $this->selectedMonthRange();

        return Application::query()
            ->with([
                'referral:id,full_name',
                'accountOfficer:id,full_name',
            ])
            // Counted in SQL rather than by loading every status row.
            ->withCount([
                'documents',
                'documents as documents_complete_count' => fn ($q) => $q->where('status', DocumentStatus::Lengkap->value),
                'trackings as trackings_done_count' => fn ($q) => $q->where('status', TrackingStatus::Selesai->value),
            ])
            ->when($this->search !== '', function ($query) {
                $term = '%'.str_replace('%', '\%', trim($this->search)).'%';

                $query->where(fn ($q) => $q
                    ->where('code', 'ilike', $term)
                    ->orWhere('debtor_name', 'ilike', $term));
            })
            ->when($this->product !== '', fn ($q) => $q->where('financing_product', $this->product))
            ->when($this->goLive === 'live', fn ($q) => $q->whereNotNull('go_live_date'))
            ->when($this->goLive === 'pipeline', fn ($q) => $q->pipeline())
            ->when($this->goLive === 'canceled', fn ($q) => $q
                ->whereNull('go_live_date')
                ->where('application_status', ApplicationStatus::Canceled->value))
            ->when($monthRange !== null, fn ($q) => $q->whereBetween('created_at', $monthRange))
            ->latest('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function products(): array
    {
        return FinancingProduct::cases();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'product', 'goLive', 'month');
        $this->resetPage();
    }

    private function selectedMonthRange(): ?array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('!Y-m-d', $this->month.'-01')->startOfMonth();
        } catch (\Throwable) {
            return null;
        }

        if ($start->format('Y-m') !== $this->month) {
            return null;
        }

        return [$start, $start->copy()->endOfMonth()];
    }

    public function render()
    {
        return view('livewire.application.application-list');
    }
}
