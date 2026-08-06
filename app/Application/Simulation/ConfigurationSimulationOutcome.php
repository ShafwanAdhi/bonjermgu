<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\Output\SimulationResult;

/**
 * Result of {@see ConfigurationSimulator}.
 *
 * Returns the config and the input alongside the result on purpose: a trace
 * that only shows outputs cannot be checked against anything.
 */
final readonly class ConfigurationSimulationOutcome
{
    public function __construct(
        public SimulationResult $result,
        public SimulationConfig $config,
        public SimulationInput $input,
        public string $vehicleLabel,
        public int $currentYear,
    ) {}
}
