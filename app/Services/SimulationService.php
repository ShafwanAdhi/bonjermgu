<?php

namespace App\Services;

use App\Application\Simulation\DatabaseSimulationInput;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\Output\SimulationResult;
use App\Domain\Simulation\SimulationEngine;
use App\Models\Referral;
use App\Repositories\SimulationConfigurationRepository;
use App\Repositories\VehicleCascadeRepository;

final class SimulationService
{
    public function __construct(
        private readonly SimulationEngine $engine,
        private readonly SimulationConfigurationRepository $configurationRepository,
        private readonly VehicleCascadeRepository $vehicleRepository,
    ) {}

    public function simulate(
        Referral $referral,
        DatabaseSimulationInput $request,
        int $currentYear,
    ): SimulationResult {
        $vehicle = $this->vehicleRepository->pricedVehicle(
            $request->vehicleModelId,
            $request->vehicleYear,
        );
        $config = $this->configurationRepository->forReferral($referral, $vehicle->usage);

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

        return $this->engine->simulate($input, $config, $currentYear);
    }
}
