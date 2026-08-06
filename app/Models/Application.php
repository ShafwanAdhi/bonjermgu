<?php

namespace App\Models;

use App\Domain\Application\ApplicationCodeGenerator;
use App\Domain\Application\DebtorType;
use App\Domain\Application\DocumentStatus;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\SpouseIncomeType;
use App\Domain\Application\TrackingStatus;
use App\Models\Scopes\ApplicationVisibilityScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Credit Application.
 *
 * Debtor data is limited to name, NIK, and birth date. Do not add columns for
 * address, income, employer, or anything else about the debtor — CLAUDE.md
 * rule 9 and business.md section 5.
 */
#[ScopedBy([ApplicationVisibilityScope::class])]
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'account_officer_id',
        'referral_id',
        'financing_product',
        'debtor_name',
        'debtor_nik',
        'debtor_birth_date',
        'debtor_type',
        'spouse_income_type',
        'amount_finance',
        'unit_count',
        'go_live_date',
    ];

    protected function casts(): array
    {
        return [
            'debtor_birth_date' => 'date',
            'go_live_date' => 'date',
            'financing_product' => FinancingProduct::class,
            'debtor_type' => DebtorType::class,
            'spouse_income_type' => SpouseIncomeType::class,
            'amount_finance' => 'integer',
            'unit_count' => 'integer',
        ];
    }

    /** Route model binding resolves on the code, never on the id. */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    protected static function booted(): void
    {
        static::creating(function (self $application) {
            $application->code ??= ApplicationCodeGenerator::generate(
                // withoutGlobalScope here is about uniqueness, not visibility:
                // a code must be unique across every application, including
                // those the current user cannot see.
                fn (string $code) => self::withoutGlobalScope(ApplicationVisibilityScope::class)
                    ->where('code', $code)
                    ->exists(),
            );
        });
    }

    public function accountOfficer(): BelongsTo
    {
        return $this->belongsTo(AccountOfficer::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(ApplicationTracking::class);
    }

    public function isGoLive(): bool
    {
        return $this->go_live_date !== null;
    }

    public function completedDocumentCount(): int
    {
        return $this->documents->where('status', DocumentStatus::Lengkap)->count();
    }

    public function completedStageCount(): int
    {
        return $this->trackings->where('status', TrackingStatus::Selesai)->count();
    }
}
