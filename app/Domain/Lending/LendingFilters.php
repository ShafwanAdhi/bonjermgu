<?php

namespace App\Domain\Lending;

/**
 * Filters for the Lending report — docs/lending.md section 8.
 *
 * All optional and combinable. The period applies to Actual Lending only;
 * Pipe Line is a current position and is never cut by it.
 */
class LendingFilters
{
    public function __construct(
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly ?string $product = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $subCategoryId = null,
        public readonly ?int $institutionId = null,
    ) {}

    public static function fromArray(array $input): self
    {
        return new self(
            from: self::date($input['from'] ?? null),
            to: self::date($input['to'] ?? null),
            product: ($input['product'] ?? '') !== '' ? $input['product'] : null,
            categoryId: ($input['category_id'] ?? '') !== '' ? (int) $input['category_id'] : null,
            subCategoryId: ($input['sub_category_id'] ?? '') !== '' ? (int) $input['sub_category_id'] : null,
            institutionId: ($input['institution_id'] ?? '') !== '' ? (int) $input['institution_id'] : null,
        );
    }

    /** Only a plain Y-m-d gets through — the value is interpolated into SQL. */
    private static function date(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    public function hasAny(): bool
    {
        return $this->from !== null
            || $this->to !== null
            || $this->product !== null
            || $this->categoryId !== null
            || $this->subCategoryId !== null
            || $this->institutionId !== null;
    }
}
