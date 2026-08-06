<?php

namespace App\Domain\Simulation;

use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\Output\SimulationResult;

final class SimulationEngine
{
    public function __construct(
        private readonly DanaTunaiCalculator $danaTunaiCalculator = new DanaTunaiCalculator,
        private readonly MobilBekasCalculator $mobilBekasCalculator = new MobilBekasCalculator,
    ) {}

    public function simulate(SimulationInput $input, SimulationConfig $config, int $currentYear): SimulationResult
    {
        return match ($input->financingType) {
            FinancingType::DTN => $this->danaTunaiCalculator->calculate($input, $config, $currentYear),
            FinancingType::UCF => $this->mobilBekasCalculator->calculate($input, $config, $currentYear),
        };
    }
}
