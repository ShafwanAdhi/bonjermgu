<?php

namespace App\Domain\Lending;

/**
 * One row of the Lending report. Money is whole rupiah as an integer, matching
 * the bigint column — no floats anywhere near it (AD-05).
 */
class LendingRow
{
    public function __construct(
        public readonly string $name,
        public readonly int $actualUnits,
        public readonly int $actualAmount,
        public readonly int $pipelineUnits,
        public readonly int $pipelineAmount,
    ) {}
}
