<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\SimulationEngine;
use App\Domain\Simulation\SimulationProfile;
use App\Models\ReferralCategory;
use App\Repositories\ProductResolver;
use App\Repositories\SimulationConfigurationRepository;
use App\Repositories\VehicleCascadeRepository;
use RuntimeException;

/**
 * Runs the engine for an Account Officer.
 *
 * Same engine, same Product mapping, same reference vectors as the Referral
 * screen. Two things differ, both decided with the client on 11 August 2026 and
 * written up in docs/credit-simulation.md section 14b:
 *
 *   1. The Officer profile prices Dana Tunai from an appraised value instead of
 *      the PHPM master, which brings Deviasi into a product that has none on the
 *      Referral screen.
 *   2. The Officer may override upping and the per-transaction insurance and tax
 *      figures for this one run. The overrides are applied to a copy of the
 *      configuration and never persisted.
 */
final class OfficerSimulator
{
    public function __construct(
        private readonly SimulationEngine $engine,
        private readonly SimulationConfigurationRepository $configurationRepository,
        private readonly VehicleCascadeRepository $vehicleRepository,
        private readonly ProductResolver $productResolver,
    ) {}

    public function run(OfficerSimulationRequest $request, int $currentYear): ConfigurationSimulationOutcome
    {
        $vehicle = $this->vehicleRepository->pricedVehicle(
            $request->vehicleModelId,
            $request->vehicleYear,
        );

        $category = ReferralCategory::query()
            ->where('is_active', true)
            ->find($request->referralCategoryId);

        if ($category === null) {
            throw new RuntimeException('Kategori Referral aktif tidak ditemukan.');
        }

        $product = $this->productResolver->resolveForCategory($category, $vehicle->usage);
        $base = $this->configurationRepository->forProduct($product, $request->rateVariant);

        $config = $base->with(
            product: $base->product->withUpping(
                $request->upRate,
                $request->upAdmin,
                $request->upProvision,
            ),
            insurance: $request->acpUpping === null
                ? null
                : $base->insurance->withAcpUpping($request->ageGroup, $request->acpUpping),
            profile: SimulationProfile::OFFICER,
            bbnkbAmount: $request->bbnkbAmount,
            pkbAmount: $request->pkbAmount,
            invoiceAmount: $request->invoiceAmount,
            depositInstalmentAmount: $request->depositInstalmentAmount,
        );

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
            extensions: $request->extensions,
            tjhAmount: $request->tjhAmount,
            driverCoverageAmount: $request->driverCoverageAmount,
            passengerCoverageAmount: $request->passengerCoverageAmount,
            passengerCount: $request->passengerCount,
            engineWarrantyEnabled: $request->engineWarrantyEnabled,
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
