<?php

namespace App\Livewire\Application;

use App\Domain\Application\ApplicationCreator;
use App\Domain\Application\DebtorType;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\SpouseIncomeType;
use App\Models\Application;
use App\Models\Referral;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Buat Credit Application — docs/pages.md section 10.
 *
 * Debtor fields are limited to name, NIK, and birth date. Do not add any other
 * debtor field here (CLAUDE.md rule 9).
 *
 * Amount Finance is typed in by the AO and is deliberately NOT carried over
 * from a simulation: the final figure follows verification and can differ from
 * the estimate (docs/application-tracking.md section 2).
 */
#[Layout('components.layouts.app')]
class CreateApplication extends Component
{
    public string $financing_product = FinancingProduct::DanaTunai->value;

    public string $debtor_name = '';

    public string $debtor_nik = '';

    public string $debtor_birth_date = '';

    public string $debtor_type = DebtorType::PeroranganNonWiraswasta->value;

    public ?string $spouse_income_type = SpouseIncomeType::TidakAda->value;

    public ?string $amount_finance = null;

    /**
     * Always 1. The client confirmed one Credit Application covers exactly
     * one unit (docs/client-decisions.md, butir 15), so this is not an input.
     */
    public int $unit_count = 1;

    /** Referral is chosen through a search, never a full dropdown. */
    public string $referralSearch = '';

    public ?int $referral_id = null;

    public function mount(): void
    {
        $this->authorize('create', Application::class);
    }

    #[Computed]
    public function referralResults(): Collection
    {
        if (mb_strlen(trim($this->referralSearch)) < 2) {
            return collect();
        }

        $term = '%'.str_replace('%', '\%', trim($this->referralSearch)).'%';

        return Referral::query()
            ->with('category:id,name', 'subCategory:id,name', 'institution:id,name')
            ->where('full_name', 'ilike', $term)
            ->orderBy('full_name')
            // The number of Referral accounts is unbounded; never load them all.
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function selectedReferral(): ?Referral
    {
        return $this->referral_id
            ? Referral::with('category', 'subCategory', 'institution')->find($this->referral_id)
            : null;
    }

    /** Spouse income only applies to an individual — document-requirement.md §5. */
    #[Computed]
    public function isIndividual(): bool
    {
        return DebtorType::from($this->debtor_type)->isIndividual();
    }

    protected function rules(): array
    {
        return [
            'financing_product' => ['required', Rule::enum(FinancingProduct::class)],
            'debtor_name' => ['required', 'string', 'max:150'],
            'debtor_nik' => ['required', 'digits:16'],
            'debtor_birth_date' => ['required', 'date', 'before:today'],
            'debtor_type' => ['required', Rule::enum(DebtorType::class)],
            'spouse_income_type' => [
                $this->isIndividual ? 'required' : 'nullable',
                Rule::enum(SpouseIncomeType::class),
            ],
            'referral_id' => ['required', Rule::exists('referrals', 'id')],
            'amount_finance' => ['nullable', 'integer', 'min:0'],
            'unit_count' => ['required', 'integer', 'in:1'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'financing_product' => 'Produk Pembiayaan',
            'debtor_name' => 'Nama Debitur',
            'debtor_nik' => 'NIK Debitur',
            'debtor_birth_date' => 'Tanggal Lahir Debitur',
            'debtor_type' => 'Type Debitur',
            'spouse_income_type' => 'Konfirmasi Sumber Penghasilan Lainnya',
            'referral_id' => 'Referral',
            'amount_finance' => 'Amount Finance',
            'unit_count' => 'Jumlah Unit',
        ];
    }

    protected function messages(): array
    {
        return [
            'debtor_nik.digits' => 'NIK Debitur harus terdiri dari 16 angka.',
            'referral_id.required' => 'Pilih Referral yang membawa debitur ini.',
        ];
    }

    public function updatedDebtorType(): void
    {
        // A legal entity stores NULL — the database enforces this too.
        $this->spouse_income_type = $this->isIndividual
            ? SpouseIncomeType::TidakAda->value
            : null;

        unset($this->isIndividual);
    }

    public function selectReferral(int $id): void
    {
        $this->referral_id = $id;
        $this->referralSearch = '';
        unset($this->referralResults, $this->selectedReferral);
    }

    public function clearReferral(): void
    {
        $this->reset('referral_id');
        unset($this->selectedReferral);
    }

    public function save()
    {
        $this->authorize('create', Application::class);

        $validated = $this->validate();

        $application = ApplicationCreator::create([
            'account_officer_id' => Auth::user()->accountOfficer->id,
            'referral_id' => $validated['referral_id'],
            'financing_product' => $validated['financing_product'],
            'debtor_name' => $validated['debtor_name'],
            'debtor_nik' => $validated['debtor_nik'],
            'debtor_birth_date' => $validated['debtor_birth_date'],
            'debtor_type' => $validated['debtor_type'],
            'spouse_income_type' => $validated['spouse_income_type'],
            'amount_finance' => $validated['amount_finance'] !== null && $validated['amount_finance'] !== ''
                ? (int) $validated['amount_finance']
                : null,
            'unit_count' => $validated['unit_count'],
        ], Auth::id());

        session()->flash('application_success', "Aplikasi {$application->code} berhasil dibuat.");

        return $this->redirectRoute('applications.show', ['application' => $application->code], navigate: true);
    }

    public function render()
    {
        return view('livewire.application.create-application');
    }
}
