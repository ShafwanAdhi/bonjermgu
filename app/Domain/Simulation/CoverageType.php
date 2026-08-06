<?php

namespace App\Domain\Simulation;

enum CoverageType: string
{
    case COMPREHENSIVE_ALL = 'comprehensive_all';
    case COMPREHENSIVE_THEN_TLO = 'comprehensive_then_tlo';
    case TLO_ALL = 'tlo_all';

    public function coverageForYear(int $year): InsuranceCoverage
    {
        return match ($this) {
            self::COMPREHENSIVE_ALL => InsuranceCoverage::COMPREHENSIVE,
            self::COMPREHENSIVE_THEN_TLO => $year === 1
                ? InsuranceCoverage::COMPREHENSIVE
                : InsuranceCoverage::TLO,
            self::TLO_ALL => InsuranceCoverage::TLO,
        };
    }
}
