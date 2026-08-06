<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\SimulationEngine;
use App\Repositories\SimulationConfigurationRepository;
use App\Repositories\VehicleCascadeRepository;

/**
 * Runs the engine against a Product chosen directly, so Admin can verify the
 * configuration they just edited.
 *
 * Deliberately NOT the same entry point as SimulationService:
 *
 *   SimulationService  resolves the Product from the Referral's category.
 *                      That is the production path and stays untouched.
 *   this class         takes the Product as input, because the Product is the
 *                      thing under test.
 *
 * Picking the Product directly is also the only way to reach a Product no
 * referral category currently maps to. Admin needs to see those figures before
 * a category is pointed at them, not after.
 *
 * No debtor data passes through here at all — a configuration check has no
 * debtor (CLAUDE.md rule 9).
 */
final class ConfigurationSimulator
{
    public function __construct(
        private readonly SimulationEngine $engine,
        private readonly SimulationConfigurationRepository $configurationRepository,
        private readonly VehicleCascadeRepository $vehicleRepository,
    ) {}

    public function run(ConfigurationSimulationRequest $request, int $currentYear): ConfigurationSimulationOutcome
    {
        $vehicle = $this->vehicleRepository->pricedVehicle(
            $request->vehicleModelId,
            $request->vehicleYear,
        );

        $config = $this->configurationRepository->forProduct($request->product);

        $input = new SimulationInput(
            financingType: $request->financingType,
            mode: $request->mode,
            debtorType: $request->debtorType,
            ageGroup: $request->ageGroup,
            vehicleUsage: $vehicle->usage,
            vehicleOrigin: $vehicle->origin,
            stnkOwnership: $request->stnkOwnership,
            vehicleYear: $vehicle->year,
            phpmPrice: $vehicle->price,
            instalmentType: $request->instalmentType,
            coverageType: $request->coverageType,
            marketPrice: $request->marketPrice,
            desiredAmount: $request->desiredAmount,
            extensions: $config->defaultExtensions,
            tjhAmount: $config->defaultTjhAmount,
            driverCoverageAmount: $config->defaultDriverCoverageAmount,
            passengerCoverageAmount: $config->defaultPassengerCoverageAmount,
            passengerCount: $config->defaultPassengerCount,
            engineWarrantyEnabled: $config->defaultEngineWarrantyEnabled,
        );

        return new ConfigurationSimulationOutcome(
            result: $this->engine->simulate($input, $config, $currentYear),
            config: $config,
            input: $input,
            vehicleLabel: trim("{$vehicle->brand} {$vehicle->type} {$vehicle->model}"),
            currentYear: $currentYear,
        );
    }
}
