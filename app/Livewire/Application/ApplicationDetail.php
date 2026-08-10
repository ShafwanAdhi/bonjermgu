<?php

namespace App\Livewire\Application;

use App\Domain\Application\DebtorType;
use App\Domain\Application\DocumentReconciler;
use App\Domain\Application\DocumentStatus;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\SpouseIncomeType;
use App\Domain\Application\TrackingStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationTracking;
use App\Models\TrackingStage;
use App\Support\RupiahInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detail Aplikasi — docs/pages.md section 11. Three sections on one page.
 *
 * AO changes things, Referral sees exactly the same content with no controls.
 * Every mutation re-authorizes: hiding a button is presentation, the policy is
 * the gate.
 */
#[Layout('components.layouts.app')]
class ApplicationDetail extends Component
{
    public Application $application;

    public bool $editing = false;

    public bool $confirmingGoLive = false;

    /* Edit form */
    public string $financing_product = '';

    public string $debtor_name = '';

    public string $debtor_nik = '';

    public string $debtor_birth_date = '';

    public string $debtor_type = '';

    public ?string $spouse_income_type = null;

    public ?string $amount_finance = null;

    public int $unit_count = 1;

    public function mount(Application $application): void
    {
        $this->authorize('view', $application);

        $this->application = $application;
        $this->fillFromModel();
    }

    #[Computed]
    public function canEdit(): bool
    {
        return Auth::user()->can('update', $this->application);
    }

    #[Computed]
    public function documents(): Collection
    {
        return ApplicationDocument::query()
            ->with('requirement')
            ->where('application_id', $this->application->id)
            ->get()
            // Ordered by the catalogue, grouped by subject — pages.md §11.
            ->sortBy([
                fn ($a, $b) => $a->requirement->group_name <=> $b->requirement->group_name,
                fn ($a, $b) => $a->requirement->sort_order <=> $b->requirement->sort_order,
            ])
            ->values();
    }

    #[Computed]
    public function trackings(): Collection
    {
        return ApplicationTracking::query()
            ->with('stage')
            ->where('application_id', $this->application->id)
            ->orderBy('stage_no')
            ->get();
    }

    #[Computed]
    public function documentSummary(): string
    {
        $documents = $this->documents;

        return $documents->where('status', DocumentStatus::Lengkap)->count().' / '.$documents->count();
    }

    #[Computed]
    public function trackingSummary(): string
    {
        return $this->trackings->where('status', TrackingStatus::Selesai)->count().' / 11';
    }

    #[Computed]
    public function isIndividual(): bool
    {
        return DebtorType::from($this->debtor_type)->isIndividual();
    }

    /** True when the pending edit would rebuild the document list. */
    #[Computed]
    public function determinantsChanged(): bool
    {
        return $this->debtor_type !== $this->application->debtor_type->value
            || $this->spouse_income_type !== $this->application->spouse_income_type?->value;
    }

    protected function rules(): array
    {
        return [
            'financing_product' => ['required', Rule::enum(FinancingProduct::class)],
            'debtor_name' => ['required', 'string', 'max:150'],
            'debtor_nik' => [$this->isIndividual ? 'required' : 'nullable', 'digits:16'],
            'debtor_birth_date' => [$this->isIndividual ? 'required' : 'nullable', 'date', 'before:today'],
            'debtor_type' => ['required', Rule::enum(DebtorType::class)],
            'spouse_income_type' => [
                $this->isIndividual ? 'required' : 'nullable',
                Rule::enum(SpouseIncomeType::class),
            ],
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
            'amount_finance' => 'Amount Finance',
            'unit_count' => 'Jumlah Unit',
        ];
    }

    protected function messages(): array
    {
        return ['debtor_nik.digits' => 'NIK Debitur harus terdiri dari 16 angka.'];
    }

    public function updatedDebtorType(): void
    {
        $this->spouse_income_type = $this->isIndividual
            ? ($this->spouse_income_type ?? SpouseIncomeType::TidakAda->value)
            : null;

        if (! $this->isIndividual) {
            $this->reset('debtor_nik', 'debtor_birth_date');
            $this->resetValidation(['debtor_nik', 'debtor_birth_date', 'spouse_income_type']);
        }

        unset($this->isIndividual, $this->determinantsChanged);
    }

    public function edit(): void
    {
        $this->authorize('update', $this->application);

        $this->fillFromModel();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->resetValidation();
        $this->fillFromModel();
        $this->editing = false;
    }

    public function save(): void
    {
        $this->authorize('update', $this->application);

        $this->amount_finance = RupiahInput::normalize($this->amount_finance);

        $validated = $this->validate();

        // Locked once the application is live — pages.md §11.
        if ($this->application->isGoLive()) {
            $validated['financing_product'] = $this->application->financing_product->value;
        }

        $reconciliation = DB::transaction(function () use ($validated) {
            $this->application->update([
                'financing_product' => $validated['financing_product'],
                'debtor_name' => $validated['debtor_name'],
                'debtor_nik' => $this->isIndividual ? $validated['debtor_nik'] : null,
                'debtor_birth_date' => $this->isIndividual ? $validated['debtor_birth_date'] : null,
                'debtor_type' => $validated['debtor_type'],
                'spouse_income_type' => $this->isIndividual ? $validated['spouse_income_type'] : null,
                'amount_finance' => $validated['amount_finance'] !== null && $validated['amount_finance'] !== ''
                    ? (int) $validated['amount_finance']
                    : null,
                'unit_count' => $validated['unit_count'],
            ]);

            // Rebuilds the document set. Statuses that still apply are kept.
            return DocumentReconciler::reconcile($this->application->fresh(), Auth::id());
        });

        $this->application->refresh();
        $this->editing = false;
        $this->clearDerived();

        $message = 'Data aplikasi berhasil diperbarui.';

        if ($reconciliation['added'] > 0 || $reconciliation['removed'] > 0) {
            $message .= sprintf(
                ' Daftar dokumen disusun ulang: %d ditambahkan, %d dihapus, %d dipertahankan.',
                $reconciliation['added'],
                $reconciliation['removed'],
                $reconciliation['kept'],
            );
        }

        session()->flash('application_success', $message);
    }

    public function setDocumentStatus(int $documentId, string $status): void
    {
        $this->authorize('updateDocuments', $this->application);

        $document = ApplicationDocument::where('application_id', $this->application->id)
            ->whereKey($documentId)
            ->firstOrFail();

        $document->update([
            'status' => DocumentStatus::from($status),
            'updated_by' => Auth::id(),
        ]);

        $this->clearDerived();
    }

    /**
     * Stages are markable in any order and both directions. The interface must
     * not disable a stage because an earlier one is unfinished
     * (docs/application-tracking.md section 6).
     */
    public function toggleStage(int $stageNo): void
    {
        $this->authorize('updateTracking', $this->application);

        $tracking = ApplicationTracking::where('application_id', $this->application->id)
            ->where('stage_no', $stageNo)
            ->firstOrFail();

        // Marking stage 11 moves the application into Actual Lending, so it
        // asks first. Un-marking needs no confirmation.
        if ($stageNo === TrackingStage::GO_LIVE_STAGE && $tracking->status === TrackingStatus::Belum) {
            $this->confirmingGoLive = true;

            return;
        }

        $this->writeStage($tracking, $tracking->status === TrackingStatus::Selesai
            ? TrackingStatus::Belum
            : TrackingStatus::Selesai);
    }

    public function confirmGoLive(): void
    {
        $this->authorize('updateTracking', $this->application);

        $tracking = ApplicationTracking::where('application_id', $this->application->id)
            ->where('stage_no', TrackingStage::GO_LIVE_STAGE)
            ->firstOrFail();

        $this->writeStage($tracking, TrackingStatus::Selesai);

        $this->confirmingGoLive = false;

        session()->flash('application_success', 'Golive & Payment ditandai selesai. Aplikasi masuk Actual Lending.');
    }

    public function cancelGoLive(): void
    {
        $this->confirmingGoLive = false;
    }

    /** The observer fills or clears go_live_date; this never writes it. */
    private function writeStage(ApplicationTracking $tracking, TrackingStatus $status): void
    {
        $tracking->update(['status' => $status, 'updated_by' => Auth::id()]);

        $this->application->refresh();
        $this->clearDerived();
    }

    private function clearDerived(): void
    {
        unset(
            $this->documents,
            $this->trackings,
            $this->documentSummary,
            $this->trackingSummary,
            $this->determinantsChanged,
        );
    }

    private function fillFromModel(): void
    {
        $this->financing_product = $this->application->financing_product->value;
        $this->debtor_name = $this->application->debtor_name;
        $this->debtor_nik = $this->application->debtor_nik ?? '';
        $this->debtor_birth_date = $this->application->debtor_birth_date?->format('Y-m-d') ?? '';
        $this->debtor_type = $this->application->debtor_type->value;
        $this->spouse_income_type = $this->application->spouse_income_type?->value;
        $this->amount_finance = $this->application->amount_finance !== null
            ? (string) $this->application->amount_finance
            : null;
        $this->unit_count = $this->application->unit_count;
    }

    public function render()
    {
        return view('livewire.application.application-detail');
    }
}
