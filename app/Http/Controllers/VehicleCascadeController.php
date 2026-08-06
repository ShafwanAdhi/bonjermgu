<?php

namespace App\Http\Controllers;

use App\Repositories\VehicleCascadeRepository;
use Illuminate\Http\JsonResponse;

final class VehicleCascadeController extends Controller
{
    public function __construct(private readonly VehicleCascadeRepository $vehicles) {}

    public function usages(): JsonResponse
    {
        return response()->json(['data' => $this->vehicles->usages()]);
    }

    public function brands(int $usage): JsonResponse
    {
        return response()->json(['data' => $this->vehicles->brandsForUsage($usage)]);
    }

    public function types(int $brand): JsonResponse
    {
        return response()->json(['data' => $this->vehicles->typesForBrand($brand)]);
    }

    public function models(int $type): JsonResponse
    {
        return response()->json(['data' => $this->vehicles->modelsForType($type)]);
    }

    public function years(int $model): JsonResponse
    {
        return response()->json(['data' => $this->vehicles->yearsForModel($model)]);
    }
}
